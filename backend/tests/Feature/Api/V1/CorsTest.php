<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_api_acepta_peticiones_del_frontend(): void
    {
        config(['cors.allowed_origins' => ['http://localhost:5173', 'http://127.0.0.1:5173']]);

        // localhost y 127.0.0.1 son orígenes distintos para el navegador y los
        // dos hacen falta en desarrollo.
        foreach (['http://localhost:5173', 'http://127.0.0.1:5173'] as $origen) {
            $this->withHeader('Origin', $origen)
                ->getJson('/api/v1/listings')
                ->assertOk()
                ->assertHeader('Access-Control-Allow-Origin', $origen);
        }
    }

    public function test_la_api_no_abre_la_puerta_a_cualquier_origen(): void
    {
        config(['cors.allowed_origins' => ['http://localhost:5173', 'http://127.0.0.1:5173']]);

        $respuesta = $this->withHeader('Origin', 'http://sitio-cualquiera.test')
            ->getJson('/api/v1/listings');

        $this->assertNotSame(
            'http://sitio-cualquiera.test',
            $respuesta->headers->get('Access-Control-Allow-Origin')
        );
    }
}
