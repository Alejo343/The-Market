<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    protected $fillable = [
        'name',
        'color',
        'price_cents',
        'polygon',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'polygon' => 'array',
        'active' => 'boolean',
        'price_cents' => 'integer',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
