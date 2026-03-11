<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_type' => $this->item_type,
            'item_id' => $this->item_id,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'quantity' => (float) $this->quantity,
            'unit_label' => $this->isWeightItem() ? 'kg' : 'unidades',

            // Información del producto
            'product_name' => $this->getProductName(),
            'presentation' => $this->getPresentation(),

            // Usuario responsable
            'user_id' => $this->user_id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],

            'note' => $this->note,

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
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Etiqueta del tipo de movimiento
     */
    private function getTypeLabel(): string
    {
        return match ($this->type) {
            'in' => 'Entrada',
            'out' => 'Salida',
            'adjustment' => 'Ajuste',
            default => 'Desconocido'
        };
    }

    /**
     * Verificar si es item por peso
     */
    private function isWeightItem(): bool
    {
        return $this->item_type === 'App\\Models\\WeightLot';
    }

    /**
     * Obtener el nombre del producto
     */
    private function getProductName(): string
    {
        if (!$this->item) {
            return 'N/A';
        }

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
        if (!$this->item) {
            return null;
        }

        if ($this->item_type === 'App\\Models\\ProductVariant') {
            return $this->item->presentation ?? null;
        }
        return 'Por peso';
    }
}
