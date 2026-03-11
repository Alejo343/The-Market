<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeightLotResource extends JsonResource
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
            'product_id' => $this->product_id,
            'initial_weight' => $this->initial_weight,
            'available_weight' => $this->available_weight,
            'sold_weight' => $this->getSoldWeight(),
            'price_per_kg' => $this->price_per_kg,
            'expires_at' => $this->expires_at?->format('Y-m-d'),
            'active' => $this->active,
            'is_expired' => $this->isExpired(),
            'has_available_weight' => $this->hasAvailableWeight(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relaciones
            'product' => new ProductResource($this->whenLoaded('product')),
            'inventory_movements' => InventoryMovementResource::collection($this->whenLoaded('inventoryMovements')),
            'sale_items' => SaleItemResource::collection($this->whenLoaded('saleItems')),
        ];
    }
}
