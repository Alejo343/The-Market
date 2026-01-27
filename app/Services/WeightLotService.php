<?php

namespace App\Services;

use App\Models\Product;
use App\Models\WeightLot;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class WeightLotService
{
    /**
     * Lista lotes de peso con filtros opcionales
     */
    public function list(
        ?int $productId = null,
        bool $activeOnly = false,
        bool $availableOnly = false,
        bool $expiredOnly = false,
        bool $expiringSoon = false,
        ?array $include = null
    ): Collection {
        $query = WeightLot::query();

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($activeOnly) {
            $query->active();
        }

        if ($availableOnly) {
            $query->available();
        }

        if ($expiredOnly) {
            $query->expired();
        }

        if ($expiringSoon) {
            $query->expiringSoon();
        }

        if ($include) {
            $query->with($include);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtiene todos los lotes
     */
    public function getAll(): Collection
    {
        return $this->list();
    }

    /**
     * Obtiene lotes por producto
     */
    public function getByProduct(int $productId): Collection
    {
        return $this->list(productId: $productId);
    }

    /**
     * Obtiene lotes activos
     */
    public function getActive(): Collection
    {
        return $this->list(activeOnly: true);
    }

    /**
     * Obtiene lotes disponibles
     */
    public function getAvailable(): Collection
    {
        return $this->list(availableOnly: true);
    }

    /**
     * Obtiene lotes vencidos
     */
    public function getExpired(): Collection
    {
        return $this->list(expiredOnly: true);
    }

    /**
     * Obtiene lotes próximos a vencer
     */
    public function getExpiringSoon(): Collection
    {
        return $this->list(expiringSoon: true);
    }

    /**
     * Crea un nuevo lote
     */
    public function create(array $data): WeightLot
    {
        // Verificar que el producto sea de tipo 'weight'
        $product = Product::findOrFail($data['product_id']);

        if ($product->sale_type !== 'weight') {
            throw new Exception('INVALID_PRODUCT_TYPE');
        }

        $lot = WeightLot::create($data);

        // Cargar relaciones por defecto
        $lot->load('product');

        return $lot;
    }

    /**
     * Obtiene un lote específico
     */
    public function show(WeightLot $lot, ?array $include = null): WeightLot
    {
        if ($include) {
            $lot->load($include);
        } else {
            // Por defecto cargar producto
            $lot->load('product');
        }

        return $lot;
    }

    /**
     * Actualiza un lote
     */
    public function update(WeightLot $lot, array $data): WeightLot
    {
        $lot->update($data);

        $lot->load('product');

        return $lot;
    }

    /**
     * Elimina un lote
     */
    public function delete(WeightLot $lot): void
    {
        if ($lot->saleItems()->exists()) {
            throw new Exception('LOT_HAS_SALES');
        }

        $lot->delete();
    }
}
