<?php

namespace App\Console\Commands;

use App\Models\DeliveryZone;
use Illuminate\Console\Command;

class ExportDeliveryZones extends Command
{
    protected $signature = 'delivery-zones:export {--path= : Output file path}';
    protected $description = 'Export delivery zones to a JSON file for importing in other environments';

    public function handle(): int
    {
        $zones = DeliveryZone::orderBy('sort_order')->orderBy('id')->get([
            'name', 'color', 'price_cents', 'polygon', 'sort_order', 'active',
        ]);

        if ($zones->isEmpty()) {
            $this->warn('No delivery zones found.');
            return self::FAILURE;
        }

        $path = $this->option('path')
            ?? database_path('seeders/data/delivery_zones.json');

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode($zones->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Exported {$zones->count()} zone(s) to: {$path}");

        return self::SUCCESS;
    }
}
