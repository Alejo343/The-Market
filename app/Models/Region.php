<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'active',
        'parent_id',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Región padre
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'parent_id');
    }

    /**
     * Regiones hijas
     */
    public function children(): HasMany
    {
        return $this->hasMany(Region::class, 'parent_id');
    }

    /**
     * Productos de esta región
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Productos destacados de esta región
     */
    public function featuredProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'featured_products')
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    /**
     * Scope para regiones activas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Verifica si la región está activa
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Verifica si tiene productos asociados
     */
    public function hasProducts(): bool
    {
        return $this->products()->exists();
    }

    /**
     * Obtiene el conteo de productos
     */
    public function getProductsCount(): int
    {
        return $this->products()->count();
    }
}
