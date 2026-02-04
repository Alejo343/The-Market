<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'sale_type' => $this->sale_type,
            'sale_type_label' => $this->sale_type === 'unit' ? 'Por Unidad' : 'Por Peso',
            'active' => $this->active,

            // Relaciones
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),

            'brand_id' => $this->brand_id,
            'brand' => new BrandResource($this->whenLoaded('brand')),

            // Variantes o lotes según tipo de venta
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'weight_lots' => WeightLotResource::collection($this->whenLoaded('weightLots')),

            'variants_count' => $this->when(
                $this->relationLoaded('variants'),
                fn() => $this->variants->count()
            ),

            // Imágenes
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'primary_image' => $this->when(
                $this->relationLoaded('media'),
                fn() => $this->primaryImage()
                    ? new MediaResource($this->primaryImage())
                    : null
            ),
            'images_count' => $this->when(
                $this->relationLoaded('media'),
                fn() => $this->media->count()
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
