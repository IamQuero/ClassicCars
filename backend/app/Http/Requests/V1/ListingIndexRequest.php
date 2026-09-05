<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class ListingIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand' => ['sometimes', 'string', 'max:60'],
            'model' => ['sometimes', 'string', 'max:60'],
            'fuel' => ['sometimes', 'in:petrol,diesel,electric,hybrid'],
            'transmission' => ['sometimes', 'in:manual,automatic'],
            'price_min' => ['sometimes', 'numeric', 'min:0'],
            // El gte solo aplica si llega el extremo inferior: sin esto, pedir
            // solo price_max fallaba porque comparaba contra un campo ausente.
            'price_max' => array_values(array_filter([
                'sometimes', 'numeric', 'min:0',
                $this->filled('price_min') ? 'gte:price_min' : null,
            ])),
            'year_min' => ['sometimes', 'integer', 'min:1900'],
            'year_max' => array_values(array_filter([
                'sometimes', 'integer', 'min:1900',
                $this->filled('year_min') ? 'gte:year_min' : null,
            ])),
            'mileage_max' => ['sometimes', 'integer', 'min:0'],
            'sort' => ['sometimes', 'in:recent,price_asc,price_desc,year_desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
