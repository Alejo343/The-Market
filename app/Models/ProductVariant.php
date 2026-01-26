<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'presentation',
        'sku',
        'price',
        'sale_price',
        'stock',
        'min_stock',
        'tax_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock' => 'integer',
        'min_stock' => 'integer',
    ];

    /**
     * Producto al que pertenece
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Impuesto aplicable
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
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
     * Scope para variantes con stock bajo
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    /**
     * Scope para variantes sin stock
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock', 0);
    }

    /**
     * Scope para variantes en stock
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Scope para variantes con precio de oferta
     */
    public function scopeOnSale($query)
    {
        return $query->whereNotNull('sale_price');
    }

    /**
     * Obtiene el precio final (oferta o regular)
     */
    public function getFinalPrice(): float
    {
        return $this->sale_price ?? $this->price;
    }

    /**
     * Calcula el precio con impuesto incluido
     */
    public function getPriceWithTax(): float
    {
        $price = $this->getFinalPrice();
        return $this->tax->calculatePriceWithTax($price);
    }

    /**
     * Verifica si tiene stock bajo
     */
    public function hasLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    /**
     * Verifica si está en stock
     */
    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Aumenta el stock
     */
    public function increaseStock(int $quantity): void
    {
        $this->increment('stock', $quantity);
    }

    /**
     * Disminuye el stock
     */
    public function decreaseStock(int $quantity): void
    {
        $this->decrement('stock', $quantity);
    }
}
