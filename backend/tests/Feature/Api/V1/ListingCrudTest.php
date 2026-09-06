<?php

namespace Tests\Feature\Api\V1;

use App\Models\Car;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListingCrudTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'price' => 28500,
            'status' => 'published',
            'car' => [
                'brand' => 'BMW',
                'model' => 'E30 325i',
                'year' => 1989,
                'mileage' => 186000,
                'transmission' => 'manual',
                'fuel' => 'petrol',
                'description' => 'Un clásico muy cuidado.',
            ],
        ], $overrides);
    }

    public function test_un_vendedor_publica_un_anuncio_con_su_coche(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Sanctum::actingAs($seller);

        $response = $this->postJson('/api/v1/listings', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.price', 28500)
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.car.brand', 'BMW')
            ->assertJsonPath('data.seller.id', $seller->id);

        $this->assertNotNull($response->json('data.published_at'));
        $this->assertDatabaseHas('listings', ['seller_id' => $seller->id, 'status' => 'published']);
        $this->assertDatabaseHas('cars', ['brand' => 'BMW', 'model' => 'E30 325i']);
    }

    public function test_un_borrador_se_crea_sin_fecha_de_publicacion(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'seller']));

        $this->postJson('/api/v1/listings', $this->payload(['status' => 'draft']))
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.published_at', null);
    }

    public function test_un_comprador_no_puede_publicar(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'buyer']));

        $this->postJson('/api/v1/listings', $this->payload())->assertForbidden();

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_un_invitado_no_puede_publicar(): void
    {
        $this->postJson('/api/v1/listings', $this->payload())->assertUnauthorized();
    }

    public function test_la_creacion_valida_los_datos_del_coche_y_del_anuncio(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'seller']));

        $this->postJson('/api/v1/listings', [
            'price' => -5,
            'car' => ['brand' => '', 'year' => 1800, 'fuel' => 'nuclear'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors([
                'price', 'car.brand', 'car.model', 'car.year',
                'car.mileage', 'car.transmission', 'car.fuel',
            ]);
    }

    public function test_el_vendedor_edita_su_anuncio_y_los_datos_del_coche(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $listing = Listing::factory()->create(['seller_id' => $seller->id, 'price' => 20000]);
        Sanctum::actingAs($seller);

        $this->patchJson("/api/v1/listings/{$listing->id}", [
            'price' => 24500,
            'car' => ['mileage' => 190000],
        ])->assertOk()
            ->assertJsonPath('data.price', 24500)
            ->assertJsonPath('data.car.mileage', 190000);

        $this->assertDatabaseHas('listings', ['id' => $listing->id, 'price' => 24500]);
    }

    public function test_publicar_un_borrador_sella_la_fecha_de_publicacion(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $listing = Listing::factory()->draft()->create(['seller_id' => $seller->id]);
        Sanctum::actingAs($seller);

        $response = $this->patchJson("/api/v1/listings/{$listing->id}", ['status' => 'published']);

        $response->assertOk()->assertJsonPath('data.status', 'published');
        $this->assertNotNull($response->json('data.published_at'));
    }

    public function test_republicar_no_pisa_la_fecha_de_publicacion_original(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $publicado = now()->subYear()->startOfSecond();
        $listing = Listing::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'sold',
            'published_at' => $publicado,
        ]);
        Sanctum::actingAs($seller);

        $this->patchJson("/api/v1/listings/{$listing->id}", ['status' => 'published'])
            ->assertOk()
            ->assertJsonPath('data.published_at', $publicado->toIso8601String());
    }

    public function test_otro_vendedor_no_puede_editar_un_anuncio_ajeno(): void
    {
        $listing = Listing::factory()->create(['price' => 20000]);
        Sanctum::actingAs(User::factory()->create(['role' => 'seller']));

        $this->patchJson("/api/v1/listings/{$listing->id}", ['price' => 1])->assertForbidden();

        $this->assertDatabaseHas('listings', ['id' => $listing->id, 'price' => 20000]);
    }

    public function test_retirar_un_anuncio_lo_saca_del_catalogo_sin_borrarlo(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $listing = Listing::factory()->create(['seller_id' => $seller->id]);
        Sanctum::actingAs($seller);

        $this->deleteJson("/api/v1/listings/{$listing->id}")->assertOk();

        $this->assertDatabaseHas('listings', ['id' => $listing->id, 'status' => 'expired']);

        $this->getJson('/api/v1/listings')->assertJsonCount(0, 'data');
    }

    public function test_otro_vendedor_no_puede_retirar_un_anuncio_ajeno(): void
    {
        $listing = Listing::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => 'seller']));

        $this->deleteJson("/api/v1/listings/{$listing->id}")->assertForbidden();

        $this->assertDatabaseHas('listings', ['id' => $listing->id, 'status' => 'published']);
    }

    public function test_el_borrador_solo_es_visible_para_su_vendedor(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $listing = Listing::factory()->draft()->create([
            'seller_id' => $seller->id,
            'car_id' => Car::factory()->create()->id,
        ]);

        $this->getJson("/api/v1/listings/{$listing->id}")->assertNotFound();

        Sanctum::actingAs($seller);
        $this->getJson("/api/v1/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $listing->id);
    }
}
