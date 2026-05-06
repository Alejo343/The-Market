<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

class DeliveryZoneSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/delivery_zones.json');

        if (! file_exists($path)) {
            $this->command->warn("File not found: {$path}");
            $this->command->warn('Run php artisan delivery-zones:export on the source environment first.');
            return;
        }

        $zones = json_decode(file_get_contents($path), true);

        DeliveryZone::truncate();

        foreach ($zones as $zone) {
            DeliveryZone::create($zone);
        }

        $this->command->info('Imported ' . count($zones) . ' delivery zone(s).');
    }
}
