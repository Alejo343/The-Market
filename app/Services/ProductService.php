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
        ?string $status = null,
        ?string $search = null,
        ?array $include = null,
        bool $noBrand = false,
        bool $noCategory = false,
        bool $noRegion = false,
        int $perPage = 15,
        string $sortDirection = 'asc'
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

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->where('active', false);
        }

        if ($search) {
            $query->whereRaw('unaccent(name) ilike unaccent(?)', ["%{$search}%"]);
        }

        if ($include) {
            $query->with($include);
        }

        if ($noBrand) {
            $query->whereNull('brand_id');
        }
        if ($noCategory) {
            $query->whereNull('category_id');
        }
        if ($noRegion) {
            $query->whereNull('region_id');
        }

        return $query->orderBy('name', $sortDirection)->paginate($perPage);
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
        return Product::query()
            ->whereRaw('unaccent(name) ilike unaccent(?)', ["%{$query}%"])
            ->orderBy('name')
            ->get();
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
     * Actualiza masivamente nombre, descripción y precio de productos
     *
     * @param  array<array{id: int, name?: string, description?: string, price?: numeric}> $items
     * @return array{updated: int[], errors: array<int, string[]>}
     */
    public function bulkUpdate(array $items): array
    {
        $updated = [];
        $errors  = [];

        foreach ($items as $item) {
            $id   = $item['id'];
            $data = array_intersect_key($item, array_flip(['name', 'description', 'price']));

            $product = Product::find($id);

            if (!$product) {
                $errors[$id] = ['Producto no encontrado'];
                continue;
            }

            $product->update($data);
            $updated[] = $id;
        }

        return compact('updated', 'errors');
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
