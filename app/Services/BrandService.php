<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class BrandService
{
    /**
     * Lista marcas con filtros opcionales
     */
    public function list(
        ?array $include = null,
        ?string $search = null
    ): Collection {
        $query = Brand::query();

        if ($include) {
            $query->with($include);
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Obtiene todas las marcas
     */
    public function getAll(): Collection
    {
        return $this->list();
    }

    /**
     * Busca marcas por nombre
     */
    public function search(string $query): Collection
    {
        return $this->list(search: $query);
    }

    /**
     * Crea una nueva marca
     */
    public function create(array $data): Brand
    {
        return Brand::create($data);
    }

    /**
     * Obtiene una marca específica
     */
    public function show(Brand $brand, ?array $include = null): Brand
    {
        if ($include) {
            $brand->load($include);
        }

        return $brand;
    }

    /**
     * Actualiza una marca
     */
    public function update(Brand $brand, array $data): Brand
    {
        $brand->update($data);

        return $brand;
    }

    /**
     * Elimina una marca
     */
    public function delete(Brand $brand): void
    {
        if ($brand->products()->exists()) {
            throw new Exception('BRAND_HAS_PRODUCTS');
        }

        $brand->delete();
    }
}
