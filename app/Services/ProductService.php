<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class ProductService
{
    /**
     * Lista productos con filtros opcionales
     */
    public function list(
        ?int $categoryId = null,
        ?int $brandId = null,
        ?string $saleType = null,
        bool $activeOnly = false,
        ?string $search = null,
        ?array $include = null
    ): Collection {
        $query = Product::query();

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        if ($saleType) {
            $query->where('sale_type', $saleType);
        }

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
     * Obtiene todos los productos
     */
    public function getAll(): Collection
    {
        return $this->list();
    }

    /**
     * Obtiene productos activos
     */
    public function getActive(): Collection
    {
        return $this->list(activeOnly: true);
    }

    /**
     * Obtiene productos por categoría
     */
    public function getByCategory(int $categoryId): Collection
    {
        return $this->list(categoryId: $categoryId);
    }

    /**
     * Obtiene productos por marca
     */
    public function getByBrand(int $brandId): Collection
    {
        return $this->list(brandId: $brandId);
    }

    /**
     * Obtiene productos por tipo de venta
     */
    public function getBySaleType(string $saleType): Collection
    {
        return $this->list(saleType: $saleType);
    }

    /**
     * Busca productos por nombre
     */
    public function search(string $query): Collection
    {
        return $this->list(search: $query);
    }

    /**
     * Crea un nuevo producto
     */
    public function create(array $data): Product
    {
        $product = Product::create($data);

        // Cargar relaciones por defecto
        $product->load(['category', 'brand']);

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
            $product->load(['category', 'brand']);
        }

        return $product;
    }

    /**
     * Actualiza un producto
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        $product->load(['category', 'brand']);

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
