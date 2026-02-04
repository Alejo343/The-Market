<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'sale_type',
        'category_id',
        'brand_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Categoría del producto
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Marca del producto
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Variantes del producto (para venta por unidad)
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Lotes de peso (para venta por peso - carnicería)
     */
    public function weightLots(): HasMany
    {
        return $this->hasMany(WeightLot::class);
    }

    /**
     * Imágenes del producto
     */
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'product_media')
            ->withPivot(['is_primary', 'order'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    /**
     * Obtiene la imagen principal del producto
     */
    public function primaryImage(): ?Media
    {
        return $this->media()
            ->wherePivot('is_primary', true)
            ->first();
    }

    /**
     * Obtiene todas las imágenes secundarias
     */
    public function secondaryImages(): BelongsToMany
    {
        return $this->media()
            ->wherePivot('is_primary', false)
            ->orderByPivot('order');
    }

    /**
     * Scope para productos activos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para productos por unidad
     */
    public function scopeByUnit($query)
    {
        return $query->where('sale_type', 'unit');
    }

    /**
     * Scope para productos por peso
     */
    public function scopeByWeight($query)
    {
        return $query->where('sale_type', 'weight');
    }

    /**
     * Verifica si el producto se vende por unidad
     */
    public function isSoldByUnit(): bool
    {
        return $this->sale_type === 'unit';
    }

    /**
     * Verifica si el producto se vende por peso
     */
    public function isSoldByWeight(): bool
    {
        return $this->sale_type === 'weight';
    }

    /**
     * Obtiene el stock total disponible (para productos por unidad)
     */
    public function getTotalStock(): int
    {
        if ($this->isSoldByUnit()) {
            return $this->variants()->sum('stock');
        }

        return 0;
    }

    /**
     * Obtiene el peso total disponible (para productos por peso)
     */
    public function getTotalWeight(): float
    {
        if ($this->isSoldByWeight()) {
            return $this->weightLots()
                ->where('active', true)
                ->sum('available_weight');
        }

        return 0.0;
    }
}
