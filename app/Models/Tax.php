<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'percentage',
        'active',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'active' => 'boolean',
    ];

    /**
     * Variantes de productos que usan este impuesto
     */
    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Scope para impuestos activos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Calcula el monto de impuesto sobre un precio
     */
    public function calculateTaxAmount(float $price): float
    {
        return round($price * ($this->percentage / 100), 2);
    }

    /**
     * Calcula el precio con impuesto incluido
     */
    public function calculatePriceWithTax(float $price): float
    {
        return round($price + $this->calculateTaxAmount($price), 2);
    }
}
