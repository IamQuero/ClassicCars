<?php

namespace Tests\Feature\Api\V1;

use App\Models\Car;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiltersTest extends TestCase
{
    use RefreshDatabase;

    private function anuncio(array $car, array $listing = []): Listing
    {
        return Listing::factory()->create([
            'car_id' => Car::factory()->create($car)->id,
            ...$listing,
        ]);
    }

    public function test_devuelve_las_marcas_y_modelos_que_hay_en_el_catalogo(): void
    {
        $this->anuncio(['brand' => 'BMW', 'model' => 'E30 325i']);
        $this->anuncio(['brand' => 'BMW', 'model' => '2002 tii']);
        $this->anuncio(['brand' => 'Alfa Romeo', 'model' => 'Giulia GT']);

        $respuesta = $this->getJson('/api/v1/listings/filters')->assertOk();

        $respuesta->assertJsonPath('data.brands.0.brand', 'Alfa Romeo')
            ->assertJsonPath('data.brands.1.brand', 'BMW')
            ->assertJsonPath('data.brands.1.models', ['2002 tii', 'E30 325i']);
    }

    public function test_no_ofrece_marcas_de_anuncios_que_no_estan_visibles(): void
    {
        $this->anuncio(['brand' => 'BMW', 'model' => 'E30 325i']);
        $this->anuncio(['brand' => 'Porsche', 'model' => '944 S2'], ['status' => 'draft']);
        $this->anuncio(['brand' => 'Ferrari', 'model' => '308'], ['expires_at' => now()->subDay()]);

        $this->getJson('/api/v1/listings/filters')
            ->assertOk()
            ->assertJsonCount(1, 'data.brands')
            ->assertJsonPath('data.brands.0.brand', 'BMW');
    }

    public function test_devuelve_los_rangos_de_precio_year_y_kilometros(): void
    {
        $this->anuncio(['year' => 1972, 'mileage' => 90000], ['price' => 12000]);
        $this->anuncio(['year' => 1995, 'mileage' => 250000], ['price' => 48000]);

        $this->getJson('/api/v1/listings/filters')
            ->assertOk()
            ->assertJsonPath('data.ranges.price.min', 12000)
            ->assertJsonPath('data.ranges.price.max', 48000)
            ->assertJsonPath('data.ranges.year.min', 1972)
            ->assertJsonPath('data.ranges.year.max', 1995)
            ->assertJsonPath('data.ranges.mileage.max', 250000);
    }

    public function test_con_el_catalogo_vacio_no_revienta(): void
    {
        $this->getJson('/api/v1/listings/filters')
            ->assertOk()
            ->assertJsonCount(0, 'data.brands')
            ->assertJsonPath('data.ranges.price.min', null)
            ->assertJsonPath('data.ranges.year.min', null);
    }

    public function test_la_ruta_de_filtros_no_choca_con_el_detalle_de_un_anuncio(): void
    {
        $listing = Listing::factory()->create();

        $this->getJson("/api/v1/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $listing->id);
    }
}
