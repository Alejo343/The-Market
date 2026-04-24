<?php

namespace App\Jobs;

use App\Services\SiigoSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessSiigoWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(private array $payload) {}

    public function handle(SiigoSyncService $sync): void
    {
        $sync->syncFromPayload($this->payload);
    }
}
