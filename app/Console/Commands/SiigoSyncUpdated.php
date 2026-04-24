<?php

namespace App\Console\Commands;

use App\Models\SiigoSyncLog;
use App\Services\SiigoSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SiigoSyncUpdated extends Command
{
    protected $signature   = 'siigo:sync-updated';
    protected $description = 'Sincroniza productos actualizados en Siigo desde la última ejecución (fallback/polling)';

    private const LAST_RUN_KEY = 'siigo_sync_last_run';

    public function handle(SiigoSyncService $sync): int
    {
        $lastRun = Cache::get(self::LAST_RUN_KEY, now()->subHour()->toIso8601String());
        $now     = now()->toIso8601String();

        $this->info("Sincronizando productos actualizados desde: {$lastRun}");

        try {
            $counters = $sync->importAll(); // importAll ya filtra por updated_start si se le pasa

            // Guardar timestamp de esta ejecución
            Cache::forever(self::LAST_RUN_KEY, $now);

            $status  = $counters['errors'] === 0 ? 'success' : 'error';
            $message = "Polling: {$counters['created']} creados, {$counters['updated']} actualizados, {$counters['skipped']} omitidos, {$counters['errors']} errores.";

            SiigoSyncLog::record('polling', $status, $message, 'products.sync');
            $this->info($message);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            SiigoSyncLog::record('polling', 'error', $e->getMessage(), 'products.sync');
            return self::FAILURE;
        }
    }
}
