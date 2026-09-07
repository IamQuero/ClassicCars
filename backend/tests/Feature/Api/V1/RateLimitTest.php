<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_login_se_bloquea_tras_varios_intentos_fallidos(): void
    {
        User::factory()->create(['email' => 'adrian@classiccars.test']);

        $intento = fn () => $this->postJson('/api/v1/login', [
            'email' => 'adrian@classiccars.test',
            'password' => 'me-lo-invento',
        ]);

        foreach (range(1, 5) as $i) {
            $intento()->assertStatus(422);
        }

        $intento()->assertStatus(429);
    }
}
