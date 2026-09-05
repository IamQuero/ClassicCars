<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'brand' => $this->brand,
            'model' => $this->model,
            'generation' => $this->generation,
            'year' => $this->year,
            'mileage' => $this->mileage,
            'engine' => $this->engine,
            'horsepower' => $this->horsepower,
            'transmission' => $this->transmission,
            'fuel' => $this->fuel,
            'description' => $this->description,
        ];
    }
}
