<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ListingResource;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FavoriteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $favoritos = $request->user()->favorites()
            ->with(['car', 'photos', 'seller'])
            ->withIsFavorite($request->user())
            ->orderByDesc('favorites.created_at')
            ->paginate(15);

        return ListingResource::collection($favoritos);
    }

    public function store(Request $request, Listing $listing): JsonResponse
    {
        $this->soloPublicados($listing);

        // syncWithoutDetaching evita el error de clave duplicada al repetir la llamada.
        $request->user()->favorites()->syncWithoutDetaching([$listing->id]);

        return response()->json(['message' => 'Anuncio guardado en favoritos'], 201);
    }

    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        $request->user()->favorites()->detach($listing->id);

        return response()->json(['message' => 'Anuncio quitado de favoritos']);
    }

    /** No tiene sentido guardar el borrador de otro: para el usuario no existe. */
    private function soloPublicados(Listing $listing): void
    {
        if ($listing->status === 'draft') {
            throw new NotFoundHttpException;
        }
    }
}
