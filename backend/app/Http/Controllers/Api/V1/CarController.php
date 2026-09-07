<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CarResource;
use App\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarController extends Controller
{
    /**
     * Historial de precios de un coche: todos sus anuncios publicados alguna
     * vez, del más antiguo al más reciente. Es lo que permite ver cómo se ha
     * revalorizado un clásico concreto.
     */
    public function priceHistory(Request $request, Car $car): JsonResponse
    {
        $anuncios = $car->listings()
            ->whereNotNull('published_at')
            ->whereIn('status', ['published', 'sold', 'expired'])
            ->orderBy('published_at')
            ->get(['id', 'price', 'status', 'published_at']);

        $precios = $anuncios->map(fn ($anuncio) => [
            'listing_id' => $anuncio->id,
            'price' => (float) $anuncio->price,
            'status' => $anuncio->status,
            'published_at' => $anuncio->published_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => [
                'car' => (new CarResource($car))->toArray($request),
                'history' => $precios,
                'summary' => [
                    'count' => $precios->count(),
                    'min' => $precios->min('price'),
                    'max' => $precios->max('price'),
                    'first' => $precios->first()['price'] ?? null,
                    'last' => $precios->last()['price'] ?? null,
                ],
            ],
        ]);
    }
}
