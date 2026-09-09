<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CarResource;
use App\Models\Car;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarController extends Controller
{
    /**
     * Todo lo que el frontend necesita para pintar los filtros: qué marcas y
     * modelos hay realmente en el catálogo y entre qué valores se mueve.
     */
    public function filters(): JsonResponse
    {
        $coches = Car::query()
            ->whereIn('id', Listing::query()->visible()->select('car_id'))
            ->get(['brand', 'model', 'year', 'mileage']);

        $marcas = $coches
            ->groupBy('brand')
            ->map(fn ($delMismo, $marca) => [
                'brand' => $marca,
                'models' => $delMismo->pluck('model')->unique()->sort()->values(),
            ])
            ->sortKeys()
            ->values();

        $precios = Listing::query()->visible()->selectRaw('MIN(price) as minimo, MAX(price) as maximo')->first();

        return response()->json([
            'data' => [
                'brands' => $marcas,
                'fuels' => ['petrol', 'diesel', 'electric', 'hybrid'],
                'transmissions' => ['manual', 'automatic'],
                'sorts' => ['recent', 'price_asc', 'price_desc', 'year_desc'],
                'ranges' => [
                    'price' => [
                        'min' => $precios?->minimo !== null ? (float) $precios->minimo : null,
                        'max' => $precios?->maximo !== null ? (float) $precios->maximo : null,
                    ],
                    'year' => ['min' => $coches->min('year'), 'max' => $coches->max('year')],
                    'mileage' => ['min' => $coches->min('mileage'), 'max' => $coches->max('mileage')],
                ],
            ],
        ]);
    }

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
