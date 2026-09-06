<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización la lleva la ListingPolicy en el controlador.
    }

    public function rules(): array
    {
        return [
            'price' => ['sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            // Un anuncio se puede publicar, retirar o marcar como vendido desde aquí.
            'status' => ['sometimes', 'in:draft,published,sold,expired'],
            'expires_at' => ['sometimes', 'nullable', 'date'],

            'car' => ['sometimes', 'array'],
            'car.brand' => ['sometimes', 'string', 'max:60'],
            'car.model' => ['sometimes', 'string', 'max:60'],
            'car.generation' => ['sometimes', 'nullable', 'string', 'max:60'],
            'car.year' => ['sometimes', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'car.mileage' => ['sometimes', 'integer', 'min:0', 'max:2000000'],
            'car.engine' => ['sometimes', 'nullable', 'string', 'max:20'],
            'car.horsepower' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:2000'],
            'car.transmission' => ['sometimes', 'in:manual,automatic'],
            'car.fuel' => ['sometimes', 'in:petrol,diesel,electric,hybrid'],
            'car.description' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
