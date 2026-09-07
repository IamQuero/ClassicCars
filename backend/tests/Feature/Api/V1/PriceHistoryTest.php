<?php

namespace Tests\Feature\Api\V1;

use App\Models\Car;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_devuelve_los_anuncios_del_coche_del_mas_antiguo_al_mas_nuevo(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $car = Car::factory()->create(['brand' => 'BMW', 'model' => 'E30 325i']);

        Listing::factory()->sold()->create([
            'car_id' => $car->id, 'seller_id' => $seller->id,
            'price' => 21000, 'published_at' => now()->subYears(2),
        ]);
        Listing::factory()->sold()->create([
            'car_id' => $car->id, 'seller_id' => $seller->id,
            'price' => 24500, 'published_at' => now()->subYear(),
        ]);
        Listing::factory()->create([
            'car_id' => $car->id, 'seller_id' => $seller->id,
            'price' => 28500, 'published_at' => now(),
        ]);

        $this->getJson("/api/v1/cars/{$car->id}/price-history")
            ->assertOk()
            ->assertJsonPath('data.car.model', 'E30 325i')
            ->assertJsonCount(3, 'data.history')
            ->assertJsonPath('data.history.0.price', 21000)
            ->assertJsonPath('data.history.2.price', 28500)
            ->assertJsonPath('data.summary.count', 3)
            ->assertJsonPath('data.summary.min', 21000)
            ->assertJsonPath('data.summary.max', 28500)
            ->assertJsonPath('data.summary.first', 21000)
            ->assertJsonPath('data.summary.last', 28500);
    }

    public function test_los_borradores_no_entran_en_el_historial(): void
    {
        $car = Car::factory()->create();
        Listing::factory()->create(['car_id' => $car->id, 'price' => 30000]);
        Listing::factory()->draft()->create(['car_id' => $car->id]);

        $this->getJson("/api/v1/cars/{$car->id}/price-history")
            ->assertOk()
            ->assertJsonCount(1, 'data.history')
            ->assertJsonPath('data.summary.count', 1);
    }

    public function test_un_coche_sin_anuncios_devuelve_un_historial_vacio(): void
    {
        $car = Car::factory()->create();

        $this->getJson("/api/v1/cars/{$car->id}/price-history")
            ->assertOk()
            ->assertJsonCount(0, 'data.history')
            ->assertJsonPath('data.summary.count', 0)
            ->assertJsonPath('data.summary.min', null);
    }

    public function test_devuelve_404_si_el_coche_no_existe(): void
    {
        $this->getJson('/api/v1/cars/999/price-history')->assertNotFound();
    }
}
