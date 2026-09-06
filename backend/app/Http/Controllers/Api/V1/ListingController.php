<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ListingIndexRequest;
use App\Http\Requests\V1\StoreListingRequest;
use App\Http\Requests\V1\UpdateListingRequest;
use App\Http\Resources\V1\ListingResource;
use App\Models\Car;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ListingController extends Controller
{
    public function index(ListingIndexRequest $request)
    {
        $filters = $request->validated();

        $listings = Listing::query()
            ->with(['car', 'photos', 'seller'])
            ->where('status', 'published')
            ->filter($filters)
            ->sorted($filters['sort'] ?? null)
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return ListingResource::collection($listings);
    }

    public function show(Request $request, Listing $listing): ListingResource
    {
        // Un borrador solo lo ve su propio vendedor; para el resto no existe.
        if ($listing->status === 'draft' && $request->user()?->id !== $listing->seller_id) {
            throw new NotFoundHttpException;
        }

        $listing->load(['car', 'photos', 'seller']);

        return new ListingResource($listing);
    }

    public function store(StoreListingRequest $request): JsonResponse
    {
        $this->authorize('create', Listing::class);

        $data = $request->validated();
        $status = $data['status'] ?? 'draft';

        $listing = DB::transaction(function () use ($data, $status, $request) {
            $car = Car::create($data['car']);

            return Listing::create([
                'car_id' => $car->id,
                'seller_id' => $request->user()->id,
                'price' => $data['price'],
                'status' => $status,
                'published_at' => $status === 'published' ? now() : null,
                'expires_at' => $data['expires_at'] ?? null,
            ]);
        });

        $listing->load(['car', 'photos', 'seller']);

        return (new ListingResource($listing))->response()->setStatusCode(201);
    }

    public function update(UpdateListingRequest $request, Listing $listing): ListingResource
    {
        $this->authorize('update', $listing);

        $data = $request->validated();

        DB::transaction(function () use ($data, $listing) {
            if (! empty($data['car'])) {
                $listing->car->update($data['car']);
            }

            $listing->fill(array_intersect_key($data, array_flip(['price', 'status', 'expires_at'])));

            // La fecha de publicación se sella la primera vez que se publica.
            if ($listing->status === 'published' && $listing->published_at === null) {
                $listing->published_at = now();
            }

            $listing->save();
        });

        $listing->load(['car', 'photos', 'seller']);

        return new ListingResource($listing);
    }

    /**
     * Retira el anuncio del catálogo. No se borra la fila: el historial de
     * precios de un coche es justo lo que queremos conservar.
     */
    public function destroy(Listing $listing): JsonResponse
    {
        $this->authorize('delete', $listing);

        $listing->update(['status' => 'expired']);

        return response()->json(['message' => 'Anuncio retirado']);
    }
}
