<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

use Exception;

class ProductService
{
    /**
     * Lista productos con filtros opcionales
     */

    public function list(
        ?int $categoryId = null,
        ?int $brandId = null,
        ?int $regionId = null,
        ?string $saleType = null,
        bool $activeOnly = false,
        ?string $search = null,
        ?array $include = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Product::query();

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        if ($regionId) {
            $query->where('region_id', $regionId);
        }

        if ($saleType) {
            $query->where('sale_type', $saleType);
        }

        if ($activeOnly) {
            $query->active();
        }

        if ($search) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        if ($include) {
            $query->with($include);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getAll(): Collection
    {
        return Product::query()->orderBy('name')->get();
    }

    public function getActive(): Collection
    {
        return Product::query()->active()->orderBy('name')->get();
    }

    public function getByCategory(int $categoryId): Collection
    {
        return Product::query()->where('category_id', $categoryId)->orderBy('name')->get();
    }

    public function getByRegion(int $regionId): Collection
    {
        return Product::query()->where('region_id', $regionId)->orderBy('name')->get();
    }

    public function getByBrand(int $brandId): Collection
    {
        return Product::query()->where('brand_id', $brandId)->orderBy('name')->get();
    }

    public function getBySaleType(string $saleType): Collection
    {
        return Product::query()->where('sale_type', $saleType)->orderBy('name')->get();
    }

    public function search(string $query): Collection
    {
        return Product::query()->where('name', 'like', "%{$query}%")->orderBy('name')->get();
    }

    /**
     * Crea un nuevo producto
     */
    public function create(array $data): Product
    {
        $product = Product::create($data);

        // Cargar relaciones por defecto
        $product->load(['category', 'brand', 'region']);

        return $product;
    }

    /**
     * Obtiene un producto específico
     */
    public function show(Product $product, ?array $include = null): Product
    {
        if ($include) {
            $product->load($include);
        } else {
            // Por defecto cargar categoría y marca
            $product->load(['category', 'brand', 'region']);
        }

        return $product;
    }

    /**
     * Actualiza un producto
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        $product->load(['category', 'brand', 'region']);

        return $product;
    }

    /**
     * Elimina un producto
     */
    public function delete(Product $product): void
    {
        if ($product->variants()->exists()) {
            throw new Exception('PRODUCT_HAS_VARIANTS');
        }

        if ($product->weightLots()->exists()) {
            throw new Exception('PRODUCT_HAS_WEIGHT_LOTS');
        }

        $product->delete();
    }
}
