<?php

namespace Tests\Feature\Api\V1;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_guarda_y_quita_un_anuncio_de_favoritos(): void
    {
        $user = User::factory()->create(['role' => 'buyer']);
        $listing = Listing::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/listings/{$listing->id}/favorite")->assertCreated();
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'listing_id' => $listing->id]);

        $this->deleteJson("/api/v1/listings/{$listing->id}/favorite")->assertOk();
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_guardar_dos_veces_el_mismo_anuncio_no_falla(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/listings/{$listing->id}/favorite")->assertCreated();
        $this->postJson("/api/v1/listings/{$listing->id}/favorite")->assertCreated();

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_no_se_puede_guardar_el_borrador_de_otro(): void
    {
        $listing = Listing::factory()->draft()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/listings/{$listing->id}/favorite")->assertNotFound();

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_un_invitado_no_puede_usar_los_favoritos(): void
    {
        $listing = Listing::factory()->create();

        $this->postJson("/api/v1/listings/{$listing->id}/favorite")->assertUnauthorized();
        $this->getJson('/api/v1/me/favorites')->assertUnauthorized();
    }

    public function test_el_listado_de_favoritos_solo_devuelve_los_propios(): void
    {
        $user = User::factory()->create();
        $mio = Listing::factory()->create();
        $deOtro = Listing::factory()->create();

        $user->favorites()->attach($mio->id);
        User::factory()->create()->favorites()->attach($deOtro->id);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me/favorites')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mio->id)
            ->assertJsonPath('data.0.is_favorite', true);
    }

    public function test_el_listado_publico_marca_los_favoritos_del_usuario(): void
    {
        $user = User::factory()->create();
        $guardado = Listing::factory()->create();
        $otro = Listing::factory()->create();
        $user->favorites()->attach($guardado->id);

        Sanctum::actingAs($user);

        $respuesta = collect($this->getJson('/api/v1/listings')->assertOk()->json('data'))
            ->keyBy('id');

        $this->assertTrue($respuesta[$guardado->id]['is_favorite']);
        $this->assertFalse($respuesta[$otro->id]['is_favorite']);
    }

    public function test_con_un_token_bearer_el_catalogo_publico_marca_los_favoritos(): void
    {
        $user = User::factory()->create();
        $guardado = Listing::factory()->create();
        $user->favorites()->attach($guardado->id);
        $token = $user->createToken('api')->plainTextToken;

        // El listado es una ruta pública: sin pedirle el usuario al guard de
        // sanctum, el token se ignoraría y is_favorite no viajaría nunca.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/listings')
            ->assertOk()
            ->assertJsonPath('data.0.is_favorite', true);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/listings/{$guardado->id}")
            ->assertOk()
            ->assertJsonPath('data.is_favorite', true);
    }

    public function test_para_un_invitado_no_aparece_la_marca_de_favorito(): void
    {
        $listing = Listing::factory()->create();

        $this->getJson('/api/v1/listings')
            ->assertOk()
            ->assertJsonMissingPath('data.0.is_favorite');

        $this->getJson("/api/v1/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.is_favorite');
    }
}
