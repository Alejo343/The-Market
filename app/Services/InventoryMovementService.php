<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\WeightLot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryMovementService
{
    /**
     * Lista movimientos de inventario con filtros opcionales
     */
    public function list(
        ?string $type = null,
        ?int $userId = null,
        ?string $itemType = null,
        ?int $itemId = null,
        ?string $date = null,
        ?string $startDate = null,
        ?string $endDate = null,
        bool $today = false,
        ?array $include = null
    ): Collection {
        $query = InventoryMovement::query();

        // Cargar relaciones por defecto
        $defaultIncludes = ['user', 'item'];
        if ($include) {
            $query->with(array_merge($defaultIncludes, $include));
        } else {
            $query->with($defaultIncludes);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($userId) {
            $query->byUser($userId);
        }

        if ($itemType) {
            $modelClass = $itemType === 'variant'
                ? ProductVariant::class
                : WeightLot::class;
            $query->where('item_type', $modelClass);
        }

        if ($itemId) {
            $query->where('item_id', $itemId);
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        if ($today) {
            $query->today();
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtiene todos los movimientos
     */
    public function getAll(): Collection
    {
        return $this->list();
    }

    /**
     * Obtiene movimientos de hoy
     */
    public function getToday(): Collection
    {
        return $this->list(today: true);
    }

    /**
     * Obtiene movimientos por tipo
     */
    public function getByType(string $type): Collection
    {
        return $this->list(type: $type);
    }

    /**
     * Obtiene movimientos por usuario
     */
    public function getByUser(int $userId): Collection
    {
        return $this->list(userId: $userId);
    }

    /**
     * Obtiene movimientos por item
     */
    public function getByItem(string $itemType, int $itemId): Collection
    {
        return $this->list(itemType: $itemType, itemId: $itemId);
    }

    /**
     * Obtiene movimientos por fecha
     */
    public function getByDate(string $date): Collection
    {
        return $this->list(date: $date);
    }

    /**
     * Obtiene movimientos entre fechas
     */
    public function getBetweenDates(string $startDate, string $endDate): Collection
    {
        return $this->list(startDate: $startDate, endDate: $endDate);
    }

    /**
     * Crea un nuevo movimiento de inventario
     */
    public function create(array $data, int $userId): InventoryMovement
    {
        try {
            DB::beginTransaction();

            $itemType = $data['item_type'];
            $itemId = $data['item_id'];
            $movementType = $data['type'];
            $quantity = $data['quantity'];

            // Determinar el modelo y aplicar el movimiento
            if ($itemType === 'variant') {
                $modelClass = ProductVariant::class;
                $this->applyVariantMovement($itemId, $movementType, $quantity);
            } else {
                $modelClass = WeightLot::class;
                $this->applyWeightLotMovement($itemId, $movementType, $quantity);
            }

            // Crear el registro del movimiento
            $movement = InventoryMovement::create([
                'item_type' => $modelClass,
                'item_id' => $itemId,
                'type' => $movementType,
                'quantity' => $quantity,
                'user_id' => $userId,
                'note' => $data['note'] ?? null,
            ]);

            DB::commit();

            $movement->load(['user', 'item']);

            return $movement;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Aplica un movimiento a una variante de producto
     */
    protected function applyVariantMovement(int $itemId, string $movementType, float $quantity): void
    {
        $item = ProductVariant::findOrFail($itemId);

        match ($movementType) {
            'in' => $item->increaseStock($quantity),
            'out' => $this->handleVariantOut($item, $quantity),
            'adjustment' => $this->handleVariantAdjustment($item, $quantity),
            default => throw new Exception('INVALID_MOVEMENT_TYPE')
        };
    }

    /**
     * Maneja salida de stock de variante
     */
    protected function handleVariantOut(ProductVariant $item, float $quantity): void
    {
        if ($item->stock < $quantity) {
            throw new Exception('INSUFFICIENT_STOCK');
        }
        $item->decreaseStock($quantity);
    }

    /**
     * Maneja ajuste de stock de variante
     */
    protected function handleVariantAdjustment(ProductVariant $item, float $quantity): void
    {
        $item->stock = max(0, $item->stock + $quantity);
        $item->save();
    }

    /**
     * Aplica un movimiento a un lote de peso
     */
    protected function applyWeightLotMovement(int $itemId, string $movementType, float $quantity): void
    {
        $item = WeightLot::findOrFail($itemId);

        match ($movementType) {
            'in' => $this->handleWeightLotIn($item, $quantity),
            'out' => $this->handleWeightLotOut($item, $quantity),
            'adjustment' => $this->handleWeightLotAdjustment($item, $quantity),
            default => throw new Exception('INVALID_MOVEMENT_TYPE')
        };
    }

    /**
     * Maneja entrada de peso
     */
    protected function handleWeightLotIn(WeightLot $item, float $quantity): void
    {
        $item->increment('available_weight', $quantity);
        $item->increment('initial_weight', $quantity);
    }

    /**
     * Maneja salida de peso
     */
    protected function handleWeightLotOut(WeightLot $item, float $quantity): void
    {
        if ($item->available_weight < $quantity) {
            throw new Exception('INSUFFICIENT_WEIGHT');
        }
        $item->reduceWeight($quantity);
    }

    /**
     * Maneja ajuste de peso
     */
    protected function handleWeightLotAdjustment(WeightLot $item, float $quantity): void
    {
        $newWeight = max(0, $item->available_weight + $quantity);
        $item->available_weight = $newWeight;

        if ($newWeight <= 0) {
            $item->active = false;
        }

        $item->save();
    }

    /**
     * Obtiene un movimiento específico
     */
    public function show(InventoryMovement $movement, ?array $include = null): InventoryMovement
    {
        $defaultIncludes = ['user', 'item.product'];

        if ($include) {
            $movement->load(array_merge($defaultIncludes, $include));
        } else {
            $movement->load($defaultIncludes);
        }

        return $movement;
    }

    /**
     * Elimina un movimiento (operación no permitida)
     */
    public function delete(InventoryMovement $movement): void
    {
        throw new Exception('OPERATION_NOT_ALLOWED');
    }
}
