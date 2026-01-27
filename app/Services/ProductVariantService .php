<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class ProductVariantService
{
    /**
     * Lista variantes con filtros opcionales
     */
    public function list(
        ?int $productId = null,
        bool $lowStockOnly = false,
        bool $outOfStockOnly = false,
        bool $inStockOnly = false,
        bool $onSaleOnly = false,
        ?string $search = null,
        ?array $include = null
    ): Collection {
        $query = ProductVariant::query();

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($lowStockOnly) {
            $query->lowStock();
        }

        if ($outOfStockOnly) {
            $query->outOfStock();
        }

        if ($inStockOnly) {
            $query->inStock();
        }

        if ($onSaleOnly) {
            $query->onSale();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('presentation', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($include) {
            $query->with($include);
        }

        return $query->orderBy('presentation')->get();
    }

    /**
     * Obtiene todas las variantes
     */
    public function getAll(): Collection
    {
        return $this->list();
    }

    /**
     * Obtiene variantes por producto
     */
    public function getByProduct(int $productId): Collection
    {
        return $this->list(productId: $productId);
    }

    /**
     * Obtiene variantes con stock bajo
     */
    public function getLowStock(): Collection
    {
        return $this->list(lowStockOnly: true);
    }

    /**
     * Obtiene variantes sin stock
     */
    public function getOutOfStock(): Collection
    {
        return $this->list(outOfStockOnly: true);
    }

    /**
     * Obtiene variantes en stock
     */
    public function getInStock(): Collection
    {
        return $this->list(inStockOnly: true);
    }

    /**
     * Obtiene variantes en oferta
     */
    public function getOnSale(): Collection
    {
        return $this->list(onSaleOnly: true);
    }

    /**
     * Busca variantes por presentación o SKU
     */
    public function search(string $query): Collection
    {
        return $this->list(search: $query);
    }

    /**
     * Crea una nueva variante
     */
    public function create(array $data): ProductVariant
    {
        // Verificar que el producto sea de tipo 'unit'
        $product = Product::findOrFail($data['product_id']);

        if ($product->sale_type !== 'unit') {
            throw new Exception('INVALID_PRODUCT_TYPE');
        }

        $variant = ProductVariant::create($data);

        // Cargar relaciones por defecto
        $variant->load(['product', 'tax']);

        return $variant;
    }

    /**
     * Obtiene una variante específica
     */
    public function show(ProductVariant $variant, ?array $include = null): ProductVariant
    {
        if ($include) {
            $variant->load($include);
        } else {
            // Por defecto cargar producto y tax
            $variant->load(['product', 'tax']);
        }

        return $variant;
    }

    /**
     * Actualiza una variante
     */
    public function update(ProductVariant $variant, array $data): ProductVariant
    {
        $variant->update($data);

        $variant->load(['product', 'tax']);

        return $variant;
    }

    /**
     * Elimina una variante
     */
    public function delete(ProductVariant $variant): void
    {
        if ($variant->saleItems()->exists()) {
            throw new Exception('VARIANT_HAS_SALES');
        }

        $variant->delete();
    }
}
