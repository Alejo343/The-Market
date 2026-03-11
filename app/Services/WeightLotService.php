<?php

namespace App\Services;

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
        ?bool $activeOnly = null,
        ?bool $availableOnly = null,
        ?bool $expiredOnly = null,
        ?bool $expiringSoon = null,
        ?string $search = null,
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

        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
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
     * Obtiene lotes activos
     */
    public function getActive(): Collection
    {
        return $this->list(activeOnly: true);
    }

    /**
     * Obtiene lotes con peso disponible
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
     * Obtiene lotes por producto
     */
    public function getByProduct(int $productId): Collection
    {
        return $this->list(productId: $productId);
    }

    /**
     * Busca lotes por nombre de producto
     */
    public function search(string $query): Collection
    {
        return $this->list(search: $query);
    }

    /**
     * Crea un nuevo lote de peso
     */
    public function create(array $data): WeightLot
    {
        $weightLot = WeightLot::create($data);

        // Cargar relaciones por defecto
        $weightLot->load(['product']);

        return $weightLot;
    }

    /**
     * Obtiene un lote específico
     */
    public function show(WeightLot $weightLot, ?array $include = null): WeightLot
    {
        if ($include) {
            $weightLot->load($include);
        } else {
            // Por defecto cargar producto
            $weightLot->load(['product']);
        }

        return $weightLot;
    }

    /**
     * Actualiza un lote de peso
     */
    public function update(WeightLot $weightLot, array $data): WeightLot
    {
        $weightLot->update($data);

        $weightLot->load(['product']);

        return $weightLot;
    }

    /**
     * Reduce el peso disponible del lote
     */
    public function reduceWeight(WeightLot $weightLot, float $weight): WeightLot
    {
        if (!$weightLot->active) {
            throw new Exception('WEIGHT_LOT_INACTIVE');
        }

        if ($weightLot->isExpired()) {
            throw new Exception('WEIGHT_LOT_EXPIRED');
        }

        if ($weight > $weightLot->available_weight) {
            throw new Exception('INSUFFICIENT_WEIGHT');
        }

        $weightLot->reduceWeight($weight);
        $weightLot->refresh();

        return $weightLot;
    }

    /**
     * Elimina un lote de peso
     */
    public function delete(WeightLot $weightLot): void
    {
        if ($weightLot->saleItems()->exists()) {
            throw new Exception('WEIGHT_LOT_HAS_SALES');
        }

        if ($weightLot->inventoryMovements()->exists()) {
            throw new Exception('WEIGHT_LOT_HAS_MOVEMENTS');
        }

        $weightLot->delete();
    }
}
