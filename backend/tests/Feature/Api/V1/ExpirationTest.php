<?php

namespace Tests\Feature\Api\V1;

use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_comando_caduca_los_anuncios_pasados_de_fecha(): void
    {
        $caducado = Listing::factory()->create(['expires_at' => now()->subDay()]);
        $vigente = Listing::factory()->create(['expires_at' => now()->addMonth()]);
        $sinFecha = Listing::factory()->create(['expires_at' => null]);

        $this->artisan('listings:expire')->assertSuccessful();

        $this->assertSame('expired', $caducado->fresh()->status);
        $this->assertSame('published', $vigente->fresh()->status);
        $this->assertSame('published', $sinFecha->fresh()->status);
    }

    public function test_el_comando_no_resucita_ni_toca_otros_estados(): void
    {
        $vendido = Listing::factory()->sold()->create(['expires_at' => now()->subDay()]);
        $borrador = Listing::factory()->draft()->create(['expires_at' => now()->subDay()]);

        $this->artisan('listings:expire')->assertSuccessful();

        $this->assertSame('sold', $vendido->fresh()->status);
        $this->assertSame('draft', $borrador->fresh()->status);
    }

    public function test_el_catalogo_no_espera_al_cron_para_ocultar_un_caducado(): void
    {
        $vigente = Listing::factory()->create(['expires_at' => now()->addMonth()]);
        Listing::factory()->create(['expires_at' => now()->subMinute()]);

        $this->getJson('/api/v1/listings')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $vigente->id);
    }
}
