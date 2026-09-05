<?php

namespace Tests\Feature\Api\V1;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_visitante_puede_registrarse_y_recibe_un_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Adrián',
            'email' => 'adrian@classiccars.test',
            'password' => 'password-seguro',
            'password_confirmation' => 'password-seguro',
            'role' => 'seller',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'adrian@classiccars.test')
            ->assertJsonPath('user.role', 'seller')
            ->assertJsonStructure(['user' => ['id', 'name', 'role', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'adrian@classiccars.test']);
        $this->assertNotSame(
            'password-seguro',
            User::where('email', 'adrian@classiccars.test')->value('password')
        );
    }

    public function test_el_registro_valida_los_datos_y_el_email_unico(): void
    {
        User::factory()->create(['email' => 'repetido@classiccars.test']);

        $this->postJson('/api/v1/register', [
            'name' => '',
            'email' => 'repetido@classiccars.test',
            'password' => '123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_un_usuario_registrado_puede_iniciar_sesion(): void
    {
        User::factory()->create([
            'email' => 'adrian@classiccars.test',
            'password' => 'password-seguro',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'adrian@classiccars.test',
            'password' => 'password-seguro',
        ])->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'role', 'email'], 'token']);
    }

    public function test_el_login_falla_con_credenciales_incorrectas(): void
    {
        User::factory()->create([
            'email' => 'adrian@classiccars.test',
            'password' => 'password-seguro',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'adrian@classiccars.test',
            'password' => 'me-lo-invento',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_me_devuelve_el_usuario_autenticado(): void
    {
        $user = User::factory()->create(['email' => 'adrian@classiccars.test']);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'adrian@classiccars.test');
    }

    public function test_las_rutas_privadas_rechazan_a_los_invitados(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->postJson('/api/v1/logout')->assertUnauthorized();
    }

    public function test_el_logout_invalida_el_token_usado(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // El guard cachea al usuario dentro del mismo test: lo reiniciamos para
        // que la siguiente petición vuelva a resolver el token contra la BD.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_el_email_de_otro_usuario_no_se_expone_en_los_anuncios(): void
    {
        Listing::factory()->create();

        $this->getJson('/api/v1/listings')
            ->assertOk()
            ->assertJsonMissingPath('data.0.seller.email');
    }
}
