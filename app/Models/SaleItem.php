<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'item_type',
        'item_id',
        'quantity',
        'price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Venta a la que pertenece
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Item vendido (ProductVariant o WeightLot)
     */
    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Verifica si es una variante de producto
     */
    public function isProductVariant(): bool
    {
        return $this->item_type === ProductVariant::class;
    }

    /**
     * Verifica si es un lote de peso
     */
    public function isWeightLot(): bool
    {
        return $this->item_type === WeightLot::class;
    }
}
