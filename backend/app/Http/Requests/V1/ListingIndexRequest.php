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
            'price_max' => ['sometimes', 'numeric', 'min:0', 'gte:price_min'],
            'year_min' => ['sometimes', 'integer', 'min:1900'],
            'year_max' => ['sometimes', 'integer', 'gte:year_min'],
            'mileage_max' => ['sometimes', 'integer', 'min:0'],
            'sort' => ['sometimes', 'in:recent,price_asc,price_desc,year_desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}