<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /** El email solo se expone cuando el usuario es el dueño de la petición. */
    protected bool $withEmail = false;

    public function withEmail(): static
    {
        $this->withEmail = true;

        return $this;
    }

    public function toArray($request): array
    {
        $isSelf = $this->withEmail || $request->user('sanctum')?->id === $this->id;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'email' => $this->when($isSelf, fn () => $this->email),
        ];
    }
}
