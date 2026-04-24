<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiigoSyncLog extends Model
{
    protected $fillable = [
        'event_type',
        'topic',
        'siigo_code',
        'siigo_id',
        'status',
        'message',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public static function record(
        string $eventType,
        string $status,
        string $message,
        ?string $topic = null,
        ?string $siigoCode = null,
        ?string $siigoId = null,
        ?array $payload = null,
    ): self {
        return self::create([
            'event_type' => $eventType,
            'status'     => $status,
            'message'    => $message,
            'topic'      => $topic,
            'siigo_code' => $siigoCode,
            'siigo_id'   => $siigoId,
            'payload'    => $payload,
        ]);
    }

    public function scopeErrors($query)
    {
        return $query->where('status', 'error');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
