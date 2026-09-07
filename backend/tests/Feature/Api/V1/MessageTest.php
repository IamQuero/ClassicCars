<?php

namespace Tests\Feature\Api\V1;

use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_comprador_escribe_al_vendedor_de_un_anuncio(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $listing = Listing::factory()->create(['seller_id' => $seller->id]);
        $buyer = User::factory()->create(['role' => 'buyer']);
        Sanctum::actingAs($buyer);

        $this->postJson("/api/v1/listings/{$listing->id}/messages", [
            'message' => '¿El coche sigue disponible?',
        ])->assertCreated()
            ->assertJsonPath('data.message', '¿El coche sigue disponible?')
            ->assertJsonPath('data.is_mine', true);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $buyer->id,
            'receiver_id' => $seller->id,
            'listing_id' => $listing->id,
        ]);
    }

    public function test_el_mensaje_se_valida(): void
    {
        $listing = Listing::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/listings/{$listing->id}/messages", ['message' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function test_no_se_puede_escribir_sobre_un_borrador(): void
    {
        $listing = Listing::factory()->draft()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/listings/{$listing->id}/messages", ['message' => 'Hola'])
            ->assertNotFound();
    }

    public function test_un_invitado_no_puede_escribir(): void
    {
        $listing = Listing::factory()->create();

        $this->postJson("/api/v1/listings/{$listing->id}/messages", ['message' => 'Hola'])
            ->assertUnauthorized();
    }

    public function test_el_vendedor_responde_a_quien_le_ha_escrito(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $listing = Listing::factory()->create(['seller_id' => $seller->id]);
        $buyer = User::factory()->create();

        Message::factory()->create([
            'sender_id' => $buyer->id,
            'receiver_id' => $seller->id,
            'listing_id' => $listing->id,
        ]);

        Sanctum::actingAs($seller);

        $this->postJson("/api/v1/listings/{$listing->id}/messages", [
            'message' => 'Sí, sigue disponible.',
            'receiver_id' => $buyer->id,
        ])->assertCreated();

        $this->assertDatabaseHas('messages', [
            'sender_id' => $seller->id,
            'receiver_id' => $buyer->id,
            'message' => 'Sí, sigue disponible.',
        ]);
    }

    public function test_el_vendedor_no_puede_escribir_a_quien_no_le_ha_escrito(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $listing = Listing::factory()->create(['seller_id' => $seller->id]);
        $desconocido = User::factory()->create();
        Sanctum::actingAs($seller);

        $this->postJson("/api/v1/listings/{$listing->id}/messages", [
            'message' => 'Publicidad no solicitada',
            'receiver_id' => $desconocido->id,
        ])->assertStatus(422)->assertJsonValidationErrors('receiver_id');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_la_bandeja_agrupa_una_entrada_por_conversacion(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $listing = Listing::factory()->create(['seller_id' => $seller->id]);
        $buyer = User::factory()->create();

        Message::factory()->create([
            'sender_id' => $buyer->id, 'receiver_id' => $seller->id,
            'listing_id' => $listing->id, 'message' => 'Primera', 'read_at' => null,
        ]);
        Message::factory()->create([
            'sender_id' => $seller->id, 'receiver_id' => $buyer->id,
            'listing_id' => $listing->id, 'message' => 'Respuesta', 'read_at' => null,
        ]);
        Message::factory()->create([
            'sender_id' => $buyer->id, 'receiver_id' => $seller->id,
            'listing_id' => $listing->id, 'message' => 'Última', 'read_at' => null,
        ]);

        Sanctum::actingAs($seller);

        $this->getJson('/api/v1/me/conversations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.with.id', $buyer->id)
            ->assertJsonPath('data.0.last_message.message', 'Última')
            ->assertJsonPath('data.0.unread', 2);
    }

    public function test_la_bandeja_separa_conversaciones_por_anuncio_e_interlocutor(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $unAnuncio = Listing::factory()->create(['seller_id' => $seller->id]);
        $otroAnuncio = Listing::factory()->create(['seller_id' => $seller->id]);
        $unComprador = User::factory()->create();
        $otroComprador = User::factory()->create();

        Message::factory()->create([
            'sender_id' => $unComprador->id, 'receiver_id' => $seller->id, 'listing_id' => $unAnuncio->id,
        ]);
        Message::factory()->create([
            'sender_id' => $otroComprador->id, 'receiver_id' => $seller->id, 'listing_id' => $unAnuncio->id,
        ]);
        Message::factory()->create([
            'sender_id' => $unComprador->id, 'receiver_id' => $seller->id, 'listing_id' => $otroAnuncio->id,
        ]);

        Sanctum::actingAs($seller);

        $this->getJson('/api/v1/me/conversations')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_la_bandeja_no_muestra_conversaciones_ajenas(): void
    {
        Message::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/me/conversations')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_el_hilo_devuelve_los_mensajes_en_orden_y_los_marca_leidos(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $listing = Listing::factory()->create(['seller_id' => $seller->id]);
        $buyer = User::factory()->create();
        $ajeno = User::factory()->create();

        $primero = Message::factory()->create([
            'sender_id' => $buyer->id, 'receiver_id' => $seller->id,
            'listing_id' => $listing->id, 'message' => 'Hola', 'read_at' => null,
        ]);
        $segundo = Message::factory()->create([
            'sender_id' => $seller->id, 'receiver_id' => $buyer->id,
            'listing_id' => $listing->id, 'message' => 'Buenas', 'read_at' => null,
        ]);
        Message::factory()->create([
            'sender_id' => $ajeno->id, 'receiver_id' => $seller->id,
            'listing_id' => $listing->id, 'message' => 'De otro hilo', 'read_at' => null,
        ]);

        Sanctum::actingAs($seller);

        $this->getJson("/api/v1/me/conversations/{$listing->id}/{$buyer->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $primero->id)
            ->assertJsonPath('data.1.id', $segundo->id)
            ->assertJsonPath('data.0.is_mine', false)
            ->assertJsonPath('data.1.is_mine', true);

        $this->assertNotNull($primero->fresh()->read_at);
        $this->assertNull($segundo->fresh()->read_at); // el propio no se marca
    }

    public function test_un_tercero_no_ve_el_hilo_de_otros(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $listing = Listing::factory()->create(['seller_id' => $seller->id]);
        $buyer = User::factory()->create();
        Message::factory()->create([
            'sender_id' => $buyer->id, 'receiver_id' => $seller->id, 'listing_id' => $listing->id,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/me/conversations/{$listing->id}/{$buyer->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
