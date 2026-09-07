<?php

namespace Tests\Feature\Api\V1;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_devuelve_los_anuncios_del_vendedor_incluidos_los_borradores(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Listing::factory()->create(['seller_id' => $seller->id]);
        Listing::factory()->draft()->create(['seller_id' => $seller->id]);
        Listing::factory()->create(); // de otro vendedor

        Sanctum::actingAs($seller);

        $this->getJson('/api/v1/me/listings')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_se_puede_filtrar_por_estado(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Listing::factory()->create(['seller_id' => $seller->id]);
        $borrador = Listing::factory()->draft()->create(['seller_id' => $seller->id]);

        Sanctum::actingAs($seller);

        $this->getJson('/api/v1/me/listings?status=draft')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $borrador->id);
    }

    public function test_un_invitado_no_puede_ver_sus_anuncios(): void
    {
        $this->getJson('/api/v1/me/listings')->assertUnauthorized();
    }
}
