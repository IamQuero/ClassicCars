<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ForgotPasswordRequest;
use App\Http\Requests\V1\LoginRequest;
use App\Http\Requests\V1\RegisterRequest;
use App\Http\Requests\V1\ResetPasswordRequest;
use App\Http\Requests\V1\UpdateProfileRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'] ?? 'buyer',
        ]);

        return response()->json([
            'user' => (new UserResource($user))->withEmail(),
            'token' => $user->createToken('api')->plainTextToken,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return response()->json([
            'user' => (new UserResource($user))->withEmail(),
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $data = $request->validated();

        $user->fill(array_intersect_key($data, array_flip(['name', 'email', 'role'])));

        if (isset($data['password'])) {
            $user->password = $data['password'];
            // Cambiar la contraseña cierra el resto de sesiones abiertas.
            $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();
        }

        $user->save();

        return (new UserResource($user))->withEmail();
    }

    /**
     * Envía el enlace de recuperación. La respuesta es siempre la misma para
     * no revelar qué emails están registrados.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->validated());

        return response()->json([
            'message' => 'Si ese email está registrado, recibirás un enlace para restablecer la contraseña.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $resultado = Password::reset(
            $request->validated(),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                // Un restablecimiento invalida todas las sesiones anteriores.
                $user->tokens()->delete();
            }
        );

        if ($resultado !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($resultado),
            ]);
        }

        return response()->json(['message' => 'Contraseña actualizada']);
    }
}
