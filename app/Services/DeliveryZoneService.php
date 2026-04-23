<?php

namespace App\Services;

use App\Models\DeliveryZone;
use Illuminate\Support\Collection;

class DeliveryZoneService
{
    public function all(): Collection
    {
        return DeliveryZone::active()->ordered()->get();
    }

    public function allIncludingInactive(): Collection
    {
        return DeliveryZone::ordered()->get();
    }

    public function store(array $data): DeliveryZone
    {
        return DeliveryZone::create($data);
    }

    public function update(DeliveryZone $zone, array $data): DeliveryZone
    {
        $zone->update($data);
        return $zone->fresh();
    }

    public function delete(DeliveryZone $zone): void
    {
        $zone->delete();
    }

    /**
     * Detecta en qué zona cae un punto (lat, lng) usando ray-casting puro en PHP.
     * Itera las zonas en orden (sort_order) y retorna la primera que contenga el punto.
     */
    public function detectZone(float $lat, float $lng): ?DeliveryZone
    {
        $zones = $this->all();

        foreach ($zones as $zone) {
            if ($zone->polygon && $this->pointInGeoJson($lat, $lng, $zone->polygon)) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * Geocodifica una dirección de texto a coordenadas usando Nominatim (OpenStreetMap).
     * Retorna [lat, lng] o null si no se encontró.
     */
    public function geocodeAddress(string $address): ?array
    {
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
        ]);

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'header' => "User-Agent: TheMarket/1.0 (davidsupremoxd@gmail.com)\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if (!$response) {
            return null;
        }

        $results = json_decode($response, true);

        if (empty($results)) {
            return null;
        }

        return [
            'lat' => (float) $results[0]['lat'],
            'lng' => (float) $results[0]['lon'],
        ];
    }

    /**
     * Ray-casting algorithm: determina si un punto (lat, lng) está dentro de un polígono GeoJSON.
     * Soporta Feature y Polygon con un anillo exterior.
     */
    private function pointInGeoJson(float $lat, float $lng, array $geojson): bool
    {
        $coordinates = null;

        if (isset($geojson['type'])) {
            if ($geojson['type'] === 'Feature' && isset($geojson['geometry'])) {
                $coordinates = $geojson['geometry']['coordinates'] ?? null;
            } elseif ($geojson['type'] === 'Polygon') {
                $coordinates = $geojson['coordinates'] ?? null;
            } elseif ($geojson['type'] === 'MultiPolygon') {
                // Para MultiPolygon comprobar cada parte
                foreach ($geojson['coordinates'] as $polygon) {
                    if ($this->rayCast($lat, $lng, $polygon[0])) {
                        return true;
                    }
                }
                return false;
            }
        }

        if (!$coordinates || !isset($coordinates[0])) {
            return false;
        }

        // Usar solo el anillo exterior (índice 0)
        return $this->rayCast($lat, $lng, $coordinates[0]);
    }

    /**
     * Ray-casting para un anillo de coordenadas GeoJSON [lng, lat].
     */
    private function rayCast(float $lat, float $lng, array $ring): bool
    {
        $inside = false;
        $n = count($ring);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $ring[$i][0]; // lng
            $yi = $ring[$i][1]; // lat
            $xj = $ring[$j][0];
            $yj = $ring[$j][1];

            $intersect = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}
