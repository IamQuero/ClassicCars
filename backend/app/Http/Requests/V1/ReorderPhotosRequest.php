<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class ReorderPhotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización la lleva la ListingPolicy en el controlador.
    }

    public function rules(): array
    {
        return [
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['integer', 'distinct', 'exists:photos,id'],
        ];
    }
}
