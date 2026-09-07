<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_api_acepta_peticiones_del_frontend(): void
    {
        config(['cors.allowed_origins' => ['http://localhost:5173']]);

        $this->withHeader('Origin', 'http://localhost:5173')
            ->getJson('/api/v1/listings')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }

    public function test_la_api_no_abre_la_puerta_a_cualquier_origen(): void
    {
        config(['cors.allowed_origins' => ['http://localhost:5173']]);

        // Con un único origen permitido la cabecera es estática, así que lo que
        // importa es que nunca devuelva el origen que pide: el navegador del
        // sitio ajeno compara ambos y bloquea la respuesta.
        $this->withHeader('Origin', 'http://sitio-cualquiera.test')
            ->getJson('/api/v1/listings')
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }
}
