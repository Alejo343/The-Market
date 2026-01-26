<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_id' => $this->sale_id,
            'item_type' => $this->item_type,
            'item_id' => $this->item_id,

            // Información del producto
            'product_name' => $this->getProductName(),
            'presentation' => $this->getPresentation(),

            'quantity' => (float) $this->quantity,
            'unit_label' => $this->isWeightItem() ? 'kg' : 'unidades',
            'price' => (float) $this->price,
            'subtotal' => (float) $this->subtotal,

            // Relación polimórfica
            'item' => $this->when(
                $this->relationLoaded('item'),
                function () {
                    if ($this->item_type === 'App\\Models\\ProductVariant') {
                        return new ProductVariantResource($this->item);
                    }
                    return new WeightLotResource($this->item);
                }
            ),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Obtener el nombre del producto
     */
    private function getProductName(): string
    {
        if ($this->item_type === 'App\\Models\\ProductVariant') {
            return $this->item->product->name ?? 'N/A';
        }
        return $this->item->product->name ?? 'N/A';
    }

    /**
     * Obtener la presentación
     */
    private function getPresentation(): ?string
    {
        if ($this->item_type === 'App\\Models\\ProductVariant') {
            return $this->item->presentation ?? null;
        }
        return 'Por peso';
    }

    /**
     * Verificar si es item por peso
     */
    private function isWeightItem(): bool
    {
        return $this->item_type === 'App\\Models\\WeightLot';
    }
}
