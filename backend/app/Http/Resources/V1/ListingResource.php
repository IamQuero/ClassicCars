<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'price' => (float) $this->price,
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'car' => new CarResource($this->whenLoaded('car')),
            'photos' => PhotoResource::collection($this->whenLoaded('photos')),
            'seller' => new UserResource($this->whenLoaded('seller')),
        ];
    }
}