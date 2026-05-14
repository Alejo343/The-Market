<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Region;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class FeaturedProductService
{
    const MIN = 4;
    const MAX = 8;

    /**
     * Obtiene los productos destacados de una región con sus relaciones
     */
    public function getForRegion(Region $region): Collection
    {
        return $region->featuredProducts()
            ->with(['media', 'category', 'brand', 'variants', 'weightLots'])
            ->active()
            ->get();
    }

    /**
     * Sincroniza los productos destacados de una región.
     * productIds debe ser un array ordenado de IDs (define el orden de display).
     * Reglas: entre MIN y MAX productos, o vacío para limpiar.
     */
    public function sync(Region $region, array $productIds): void
    {
        $count = count($productIds);

        if ($count > 0 && $count < self::MIN) {
            throw new Exception('FEATURED_MIN');
        }

        if ($count > self::MAX) {
            throw new Exception('FEATURED_MAX');
        }

        if ($count > 0) {
            $valid = Product::whereIn('id', $productIds)->active()->pluck('id');
            if ($valid->count() !== $count) {
                throw new Exception('FEATURED_INVALID_PRODUCTS');
            }
        }

        $syncData = [];
        foreach ($productIds as $index => $productId) {
            $syncData[$productId] = ['order' => $index];
        }

        $region->featuredProducts()->sync($syncData);
    }

    /**
     * Obtiene el conteo de destacados por región
     */
    public function getCount(Region $region): int
    {
        return $region->featuredProducts()->count();
    }
}
