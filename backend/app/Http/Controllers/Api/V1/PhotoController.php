<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ReorderPhotosRequest;
use App\Http\Requests\V1\StorePhotoRequest;
use App\Http\Resources\V1\PhotoResource;
use App\Models\Listing;
use App\Models\Photo;
use App\Services\ThumbnailMaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PhotoController extends Controller
{
    /** Máximo de fotos por anuncio. */
    private const MAX_PHOTOS = 15;

    public function __construct(private readonly ThumbnailMaker $miniaturas) {}

    public function store(StorePhotoRequest $request, Listing $listing): JsonResponse
    {
        $this->authorize('update', $listing);

        if ($listing->photos()->count() >= self::MAX_PHOTOS) {
            return response()->json([
                'message' => 'Un anuncio no puede tener más de '.self::MAX_PHOTOS.' fotos.',
            ], 422);
        }

        $path = $request->file('photo')->store("listings/{$listing->id}", 'public');

        $photo = $listing->photos()->create([
            'path' => $path,
            'thumbnail_path' => $this->miniaturas->generar($path),
            'type' => $request->validated('type'),
            // La primera foto es la 0, como deja el reordenado.
            'order' => $listing->photos()->max('order') === null
                ? 0
                : (int) $listing->photos()->max('order') + 1,
        ]);

        return (new PhotoResource($photo))->response()->setStatusCode(201);
    }

    public function destroy(Listing $listing, Photo $photo): JsonResponse
    {
        $this->authorize('update', $listing);

        if ($photo->listing_id !== $listing->id) {
            return response()->json(['message' => 'Esa foto no es de este anuncio.'], 404);
        }

        $photo->delete(); // El modelo se encarga de borrar los ficheros.

        return response()->json(['message' => 'Foto eliminada']);
    }

    /** Reordena las fotos del anuncio según el orden del array recibido. */
    public function reorder(ReorderPhotosRequest $request, Listing $listing): AnonymousResourceCollection
    {
        $this->authorize('update', $listing);

        $ids = $request->validated('photos');
        $delAnuncio = $listing->photos()->pluck('id')->all();

        abort_if(
            array_diff($ids, $delAnuncio) || array_diff($delAnuncio, $ids),
            422,
            'Hay que enviar exactamente las fotos de este anuncio.'
        );

        DB::transaction(function () use ($ids, $listing) {
            foreach ($ids as $posicion => $id) {
                $listing->photos()->whereKey($id)->update(['order' => $posicion]);
            }
        });

        return PhotoResource::collection($listing->photos()->get());
    }
}
