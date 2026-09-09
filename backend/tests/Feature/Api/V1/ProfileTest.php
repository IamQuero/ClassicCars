<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_usuario_edita_su_nombre_y_su_email(): void
    {
        $user = User::factory()->create(['name' => 'Adrián', 'role' => 'buyer']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me', [
            'name' => 'Adrián Quero',
            'email' => 'nuevo@classiccars.test',
            'role' => 'seller',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Adrián Quero')
            ->assertJsonPath('data.email', 'nuevo@classiccars.test')
            ->assertJsonPath('data.role', 'seller');
    }

    public function test_no_se_puede_usar_el_email_de_otro(): void
    {
        User::factory()->create(['email' => 'ocupado@classiccars.test']);
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/v1/me', ['email' => 'ocupado@classiccars.test'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_mantener_el_propio_email_no_da_error_de_duplicado(): void
    {
        $user = User::factory()->create(['email' => 'mio@classiccars.test']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me', [
            'email' => 'mio@classiccars.test',
            'name' => 'Otro nombre',
        ])->assertOk();
    }

    public function test_cambiar_la_contrasena_exige_la_actual(): void
    {
        $user = User::factory()->create(['password' => 'la-de-siempre']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me', [
            'password' => 'una-nueva-larga',
            'password_confirmation' => 'una-nueva-larga',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->patchJson('/api/v1/me', [
            'current_password' => 'me-la-invento',
            'password' => 'una-nueva-larga',
            'password_confirmation' => 'una-nueva-larga',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('la-de-siempre', $user->fresh()->password));
    }

    public function test_cambiar_la_contrasena_cierra_las_demas_sesiones(): void
    {
        $user = User::factory()->create(['password' => 'la-de-siempre']);
        $otroDispositivo = $user->createToken('movil');
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson('/api/v1/me', [
                'current_password' => 'la-de-siempre',
                'password' => 'una-nueva-larga',
                'password_confirmation' => 'una-nueva-larga',
            ])->assertOk();

        $this->assertTrue(Hash::check('una-nueva-larga', $user->fresh()->password));
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otroDispositivo->accessToken->id]);
        $this->assertDatabaseCount('personal_access_tokens', 1); // sigue viva la sesión actual
    }

    public function test_un_invitado_no_puede_editar_perfil(): void
    {
        $this->patchJson('/api/v1/me', ['name' => 'Nadie'])->assertUnauthorized();
    }

    public function test_pedir_recuperar_contrasena_envia_el_enlace(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'adrian@classiccars.test']);

        $this->postJson('/api/v1/forgot-password', ['email' => 'adrian@classiccars.test'])
            ->assertOk();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_pedirla_para_un_email_desconocido_responde_igual(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/forgot-password', ['email' => 'nadie@classiccars.test'])
            ->assertOk();

        Notification::assertNothingSent();
    }

    public function test_el_enlace_apunta_al_frontend(): void
    {
        Notification::fake();
        config(['app.frontend_url' => 'http://localhost:5173']);
        $user = User::factory()->create(['email' => 'adrian@classiccars.test']);

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $aviso) use ($user) {
            $url = $aviso->toMail($user)->actionUrl;

            return str_starts_with($url, 'http://localhost:5173/reset-password?token=');
        });
    }

    public function test_con_el_token_se_restablece_la_contrasena(): void
    {
        $user = User::factory()->create(['email' => 'adrian@classiccars.test']);
        $user->createToken('sesion-vieja');
        $token = Password::createToken($user);

        $this->postJson('/api/v1/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'contrasena-nueva',
            'password_confirmation' => 'contrasena-nueva',
        ])->assertOk();

        $this->assertTrue(Hash::check('contrasena-nueva', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_un_token_invalido_no_cambia_nada(): void
    {
        $user = User::factory()->create([
            'email' => 'adrian@classiccars.test',
            'password' => 'la-de-siempre',
        ]);

        $this->postJson('/api/v1/reset-password', [
            'token' => 'me-lo-invento',
            'email' => $user->email,
            'password' => 'contrasena-nueva',
            'password_confirmation' => 'contrasena-nueva',
        ])->assertStatus(422)->assertJsonValidationErrors('email');

        $this->assertTrue(Hash::check('la-de-siempre', $user->fresh()->password));
    }
}
