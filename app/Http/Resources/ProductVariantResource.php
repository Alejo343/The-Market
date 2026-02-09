<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'presentation' => $this->presentation,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price ? (float) $this->sale_price : null,
            'final_price' => (float) ($this->sale_price ?? $this->price),
            'has_sale' => $this->sale_price !== null,
            'stock' => $this->stock,
            'min_stock' => $this->min_stock,
            'low_stock' => $this->stock <= $this->min_stock,
            'in_stock' => $this->stock > 0,
            'tax_id' => $this->tax_id,

            // Relaciones
            'product' => new ProductResource($this->whenLoaded('product')),
            'tax' => new TaxResource($this->whenLoaded('tax')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
