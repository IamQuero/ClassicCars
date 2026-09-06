<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización la lleva la ListingPolicy en el controlador.
    }

    public function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'status' => ['sometimes', 'in:draft,published'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:today'],

            'car' => ['required', 'array'],
            'car.brand' => ['required', 'string', 'max:60'],
            'car.model' => ['required', 'string', 'max:60'],
            'car.generation' => ['sometimes', 'nullable', 'string', 'max:60'],
            'car.year' => ['required', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'car.mileage' => ['required', 'integer', 'min:0', 'max:2000000'],
            'car.engine' => ['sometimes', 'nullable', 'string', 'max:20'],
            'car.horsepower' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:2000'],
            'car.transmission' => ['required', 'in:manual,automatic'],
            'car.fuel' => ['required', 'in:petrol,diesel,electric,hybrid'],
            'car.description' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
