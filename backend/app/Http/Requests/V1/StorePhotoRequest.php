<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización la lleva la ListingPolicy en el controlador.
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'type' => ['sometimes', 'in:exterior,interior,engine,documents'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.max' => 'La foto no puede pesar más de 5 MB.',
        ];
    }
}
