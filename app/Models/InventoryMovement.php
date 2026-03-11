<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_type',
        'item_id',
        'type',
        'quantity',
        'user_id',
        'note',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    /**
     * Item afectado (ProductVariant o WeightLot)
     */
    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usuario responsable del movimiento
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para movimientos de entrada
     */
    public function scopeIn($query)
    {
        return $query->where('type', 'in');
    }

    /**
     * Scope para movimientos de salida
     */
    public function scopeOut($query)
    {
        return $query->where('type', 'out');
    }

    /**
     * Scope para ajustes
     */
    public function scopeAdjustment($query)
    {
        return $query->where('type', 'adjustment');
    }

    /**
     * Scope para movimientos de un usuario específico
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para movimientos entre fechas
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope para movimientos de hoy
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Verifica si es una entrada
     */
    public function isIn(): bool
    {
        return $this->type === 'in';
    }

    /**
     * Verifica si es una salida
     */
    public function isOut(): bool
    {
        return $this->type === 'out';
    }

    /**
     * Verifica si es un ajuste
     */
    public function isAdjustment(): bool
    {
        return $this->type === 'adjustment';
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
