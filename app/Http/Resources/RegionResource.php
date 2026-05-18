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
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'description' => $this->description,
            'active' => $this->active,

            // Región padre (solo si la relación está cargada)
            'parent' => new RegionResource($this->whenLoaded('parent')),

            // Regiones hijas (solo si la relación está cargada)
            'children' => RegionResource::collection($this->whenLoaded('children')),

            // Contador de productos (solo si la relación está cargada)
            'products_count' => $this->when(
                $this->relationLoaded('products'),
                fn () => $this->products->count()
            ),

            // Productos (solo si la relación está cargada)
            'products' => ProductResource::collection($this->whenLoaded('products')),

            // Productos de esta región + todas sus hijas
            'all_products' => ProductResource::collection($this->whenLoaded('allProducts')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
