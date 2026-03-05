<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Productos de esta región
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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
