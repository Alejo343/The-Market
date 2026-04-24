<?php

namespace App\Console\Commands;

use App\Models\SiigoSyncLog;
use App\Services\SiigoSyncService;
use Illuminate\Console\Command;
use Throwable;

class SiigoImportProducts extends Command
{
    protected $signature   = 'siigo:import-products';
    protected $description = 'Importación inicial de todos los productos desde Siigo Nube';

    public function handle(SiigoSyncService $sync): int
    {
        $this->info('Iniciando importación de productos desde Siigo...');

        try {
            $counters = $sync->importAll();

            $this->table(
                ['Creados', 'Actualizados', 'Omitidos', 'Errores'],
                [[$counters['created'], $counters['updated'], $counters['skipped'], $counters['errors']]]
            );

            $status  = $counters['errors'] === 0 ? 'success' : 'error';
            $message = "Importación completa: {$counters['created']} creados, {$counters['updated']} actualizados, {$counters['skipped']} omitidos, {$counters['errors']} errores.";

            SiigoSyncLog::record('import', $status, $message, 'products.import');

            $this->info($message);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Error fatal: ' . $e->getMessage());
            SiigoSyncLog::record('import', 'error', 'Error fatal: ' . $e->getMessage(), 'products.import');
            return self::FAILURE;
        }
    }
}
