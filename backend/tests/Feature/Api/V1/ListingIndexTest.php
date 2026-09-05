<?php

namespace Tests\Feature\Api\V1;

use App\Models\Car;
use App\Models\Listing;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingIndexTest extends TestCase
{
    use RefreshDatabase;

    private function listingFor(array $car = [], array $listing = []): Listing
    {
        return Listing::factory()->create([
            'car_id' => Car::factory()->create($car)->id,
            ...$listing,
        ]);
    }

    public function test_devuelve_los_anuncios_publicados_con_su_coche_fotos_y_vendedor(): void
    {
        $listing = $this->listingFor();
        Photo::factory()->create(['listing_id' => $listing->id, 'order' => 0]);

        $response = $this->getJson('/api/v1/listings');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $listing->id)
            ->assertJsonStructure([
                'data' => [['id', 'price', 'status', 'published_at', 'car', 'photos', 'seller']],
                'links',
                'meta',
            ]);
    }

    public function test_oculta_los_anuncios_que_no_estan_publicados(): void
    {
        $publicado = $this->listingFor();
        $this->listingFor(listing: ['status' => 'draft']);
        $this->listingFor(listing: ['status' => 'sold']);

        $this->getJson('/api/v1/listings')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $publicado->id);
    }

    public function test_filtra_por_marca_y_modelo_sin_distinguir_mayusculas(): void
    {
        $bmw = $this->listingFor(['brand' => 'BMW', 'model' => 'E30 325i']);
        $this->listingFor(['brand' => 'Porsche', 'model' => '911 3.2 Carrera']);

        $this->getJson('/api/v1/listings?brand=bmw')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $bmw->id);

        $this->getJson('/api/v1/listings?model=e30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $bmw->id);
    }

    public function test_filtra_por_combustible_cambio_year_y_kilometros(): void
    {
        $buscado = $this->listingFor([
            'fuel' => 'diesel',
            'transmission' => 'automatic',
            'year' => 1990,
            'mileage' => 90000,
        ]);
        $this->listingFor([
            'fuel' => 'petrol',
            'transmission' => 'manual',
            'year' => 1972,
            'mileage' => 300000,
        ]);

        $this->getJson('/api/v1/listings?fuel=diesel&transmission=automatic&year_min=1985&year_max=1995&mileage_max=100000')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $buscado->id);
    }

    public function test_filtra_por_rango_de_precio(): void
    {
        $barato = $this->listingFor(listing: ['price' => 10000]);
        $caro = $this->listingFor(listing: ['price' => 50000]);

        $this->getJson('/api/v1/listings?price_min=20000')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $caro->id);

        $this->getJson('/api/v1/listings?price_max=20000')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $barato->id);
    }

    public function test_ordena_por_precio_y_por_year(): void
    {
        $barato = $this->listingFor(['year' => 1970], ['price' => 10000]);
        $caro = $this->listingFor(['year' => 1995], ['price' => 50000]);

        $this->getJson('/api/v1/listings?sort=price_asc')
            ->assertJsonPath('data.0.id', $barato->id);

        $this->getJson('/api/v1/listings?sort=price_desc')
            ->assertJsonPath('data.0.id', $caro->id);

        $this->getJson('/api/v1/listings?sort=year_desc')
            ->assertJsonPath('data.0.id', $caro->id);
    }

    public function test_ordena_por_defecto_del_mas_reciente_al_mas_antiguo(): void
    {
        $antiguo = $this->listingFor(listing: ['published_at' => now()->subYear()]);
        $reciente = $this->listingFor(listing: ['published_at' => now()->subDay()]);

        $this->getJson('/api/v1/listings')
            ->assertJsonPath('data.0.id', $reciente->id)
            ->assertJsonPath('data.1.id', $antiguo->id);
    }

    public function test_pagina_los_resultados(): void
    {
        Listing::factory(8)->create();

        $this->getJson('/api/v1/listings?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 8)
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_rechaza_filtros_invalidos(): void
    {
        $this->getJson('/api/v1/listings?fuel=nuclear&sort=lo_que_sea&per_page=500')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fuel', 'sort', 'per_page']);
    }

    public function test_muestra_un_anuncio_concreto(): void
    {
        $listing = $this->listingFor(['brand' => 'Alfa Romeo']);

        $this->getJson("/api/v1/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $listing->id)
            ->assertJsonPath('data.car.brand', 'Alfa Romeo');
    }

    public function test_devuelve_404_si_el_anuncio_no_existe(): void
    {
        $this->getJson('/api/v1/listings/999')->assertNotFound();
    }
}
