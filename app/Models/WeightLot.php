<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class WeightLot extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'initial_weight',
        'available_weight',
        'price_per_kg',
        'expires_at',
        'active',
    ];

    protected $casts = [
        'initial_weight' => 'decimal:3',
        'available_weight' => 'decimal:3',
        'price_per_kg' => 'decimal:2',
        'expires_at' => 'date',
        'active' => 'boolean',
    ];

    /**
     * Producto al que pertenece
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Movimientos de inventario
     */
    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'item');
    }

    /**
     * Items de venta
     */
    public function saleItems(): MorphMany
    {
        return $this->morphMany(SaleItem::class, 'item');
    }

    /**
     * Scope para lotes activos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para lotes con peso disponible
     */
    public function scopeAvailable($query)
    {
        return $query->where('available_weight', '>', 0);
    }

    /**
     * Scope para lotes vencidos
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now());
    }

    /**
     * Scope para lotes próximos a vencer (7 días)
     */
    public function scopeExpiringSoon($query)
    {
        return $query->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', now())
            ->whereDate('expires_at', '<=', now()->addDays(7));
    }

    /**
     * Verifica si el lote está vencido
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verifica si tiene peso disponible
     */
    public function hasAvailableWeight(): bool
    {
        return $this->available_weight > 0;
    }

    /**
     * Obtiene el peso vendido
     */
    public function getSoldWeight(): float
    {
        return $this->initial_weight - $this->available_weight;
    }

    /**
     * Reduce el peso disponible
     */
    public function reduceWeight(float $weight): void
    {
        if ($weight > $this->available_weight) {
            throw new \Exception('No hay suficiente peso disponible');
        }

        $this->decrement('available_weight', $weight);

        if ($this->available_weight <= 0) {
            $this->update(['active' => false]);
        }
    }
}
