<?php

namespace App\Services;

use App\Models\Region;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class RegionService
{
    /**
     * Lista regiones con filtros opcionales
     */
    public function list(
        bool $activeOnly = false,
        ?string $search = null,
        ?array $include = null
    ): Collection {
        $query = Region::query();

        if ($activeOnly) {
            $query->active();
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($include) {
            $query->with($include);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Obtiene todas las regiones
     */
    public function getAll(): Collection
    {
        return $this->list();
    }

    /**
     * Obtiene regiones activas
     */
    public function getActive(): Collection
    {
        return $this->list(activeOnly: true);
    }

    /**
     * Busca regiones por nombre
     */
    public function search(string $query): Collection
    {
        return $this->list(search: $query);
    }

    /**
     * Crea una nueva región
     */
    public function create(array $data): Region
    {
        return Region::create($data);
    }

    /**
     * Obtiene una región específica
     */
    public function show(Region $region, ?array $include = null): Region
    {
        if ($include) {
            $region->load($include);
        }

        return $region;
    }

    /**
     * Actualiza una región
     */
    public function update(Region $region, array $data): Region
    {
        $region->update($data);

        return $region->fresh();
    }

    /**
     * Elimina una región
     */
    public function delete(Region $region): void
    {
        // Verificar si tiene productos asociados
        if ($region->hasProducts()) {
            throw new Exception('REGION_HAS_PRODUCTS');
        }

        $region->delete();
    }

    /**
     * Obtiene el conteo de productos por región
     */
    public function getProductsCount(Region $region): int
    {
        return $region->getProductsCount();
    }
}
