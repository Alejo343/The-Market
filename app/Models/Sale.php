<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel',
        'user_id',
        'subtotal',
        'tax_total',
        'total',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Usuario que realizó la venta (cajero)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Items de la venta
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Scope para ventas de mostrador
     */
    public function scopeStore($query)
    {
        return $query->where('channel', 'store');
    }

    /**
     * Scope para ventas online
     */
    public function scopeOnline($query)
    {
        return $query->where('channel', 'online');
    }

    /**
     * Scope para ventas de hoy
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope para ventas entre fechas
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
