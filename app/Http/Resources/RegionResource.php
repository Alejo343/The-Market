<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'active' => $this->active,

            // Contador de productos (solo si la relación está cargada)
            'products_count' => $this->when(
                $this->relationLoaded('products'),
                fn() => $this->products->count()
            ),

            // Productos (solo si la relación está cargada)
            'products' => ProductResource::collection($this->whenLoaded('products')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
