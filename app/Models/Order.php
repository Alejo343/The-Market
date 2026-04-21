<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'reference',
        'transaction_id',
        'status',
        'payment_method',
        'customer_email',
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_city',
        'items_data',
        'total_amount_cents',
        'notes',
    ];

    protected $casts = [
        'items_data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
