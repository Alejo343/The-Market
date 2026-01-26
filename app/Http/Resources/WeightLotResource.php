<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeightLotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'initial_weight' => (float) $this->initial_weight,
            'available_weight' => (float) $this->available_weight,
            'sold_weight' => (float) ($this->initial_weight - $this->available_weight),
            'price_per_kg' => (float) $this->price_per_kg,
            'expires_at' => $this->expires_at?->toDateString(),
            'is_expired' => $this->expires_at ? $this->expires_at->isPast() : false,
            'active' => $this->active,
            'is_available' => $this->active && $this->available_weight > 0,

            // Relaciones
            'product' => new ProductResource($this->whenLoaded('product')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
