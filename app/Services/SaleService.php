<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\WeightLot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class SaleService
{
    /**
     * Lista ventas con filtros opcionales
     */
    public function list(
        ?string $channel = null,
        ?int $userId = null,
        ?string $date = null,
        ?string $startDate = null,
        ?string $endDate = null,
        bool $today = false,
        ?array $include = null
    ): Collection {
        $query = Sale::query();

        // Cargar relaciones por defecto
        $defaultIncludes = ['user', 'items.item'];
        if ($include) {
            $query->with(array_merge($defaultIncludes, $include));
        } else {
            $query->with($defaultIncludes);
        }

        if ($channel) {
            $query->where('channel', $channel);
        }

        if ($userId) {
            $query->where('user_id', $userId);
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
     * Obtiene todas las ventas
     */
    public function getAll(): Collection
    {
        return $this->list();
    }

    /**
     * Obtiene ventas de hoy
     */
    public function getToday(): Collection
    {
        return $this->list(today: true);
    }

    /**
     * Obtiene ventas por canal
     */
    public function getByChannel(string $channel): Collection
    {
        return $this->list(channel: $channel);
    }

    /**
     * Obtiene ventas por usuario
     */
    public function getByUser(int $userId): Collection
    {
        return $this->list(userId: $userId);
    }

    /**
     * Obtiene ventas por fecha
     */
    public function getByDate(string $date): Collection
    {
        return $this->list(date: $date);
    }

    /**
     * Obtiene ventas entre fechas
     */
    public function getBetweenDates(string $startDate, string $endDate): Collection
    {
        return $this->list(startDate: $startDate, endDate: $endDate);
    }

    /**
     * Crea una nueva venta
     */
    public function create(array $data, int $userId): Sale
    {
        try {
            DB::beginTransaction();

            $subtotal = 0;
            $taxTotal = 0;
            $saleItems = [];

            // Procesar cada item
            foreach ($data['items'] as $itemData) {
                $itemType = $itemData['type'];
                $itemId = $itemData['id'];
                $quantity = $itemData['quantity'];

                if ($itemType === 'variant') {
                    // Producto por unidad
                    $result = $this->processVariantItem($itemId, $quantity);
                } else {
                    // Producto por peso (carnicería)
                    $result = $this->processWeightLotItem($itemId, $quantity);
                }

                $saleItems[] = $result['item'];
                $subtotal += $result['subtotal'];
                $taxTotal += $result['tax'];
            }

            // Crear la venta
            $sale = Sale::create([
                'channel' => $data['channel'],
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
            ]);

            // Crear los items de venta
            $sale->items()->createMany($saleItems);

            DB::commit();

            // Cargar relaciones para la respuesta
            $sale->load(['user', 'items.item']);

            return $sale;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Procesa un item de tipo variante (unidad)
     */
    protected function processVariantItem(int $variantId, int $quantity): array
    {
        $variant = ProductVariant::with('tax')->findOrFail($variantId);

        // Verificar stock
        if ($variant->stock < $quantity) {
            throw new Exception("INSUFFICIENT_STOCK: {$variant->presentation}");
        }

        $price = $variant->getFinalPrice();
        $itemSubtotal = $price * $quantity;
        $itemTax = $variant->tax->calculateTaxAmount($itemSubtotal);

        // Reducir stock
        $variant->decreaseStock($quantity);

        return [
            'item' => [
                'item_type' => ProductVariant::class,
                'item_id' => $variant->id,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $itemSubtotal,
            ],
            'subtotal' => $itemSubtotal,
            'tax' => $itemTax,
        ];
    }

    /**
     * Procesa un item de tipo lote de peso (carnicería)
     */
    protected function processWeightLotItem(int $lotId, float $quantity): array
    {
        $lot = WeightLot::findOrFail($lotId);

        // Verificar peso disponible
        if ($lot->available_weight < $quantity) {
            throw new Exception("INSUFFICIENT_WEIGHT");
        }

        // Verificar que esté activo
        if (!$lot->active) {
            throw new Exception("INACTIVE_LOT");
        }

        $price = $lot->price_per_kg;
        $itemSubtotal = $price * $quantity;

        // Para carnicería, asumo 5% de IVA (ajusta según necesites)
        $itemTax = $itemSubtotal * 0.05;

        // Reducir peso disponible
        $lot->reduceWeight($quantity);

        return [
            'item' => [
                'item_type' => WeightLot::class,
                'item_id' => $lot->id,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $itemSubtotal,
            ],
            'subtotal' => $itemSubtotal,
            'tax' => $itemTax,
        ];
    }

    /**
     * Obtiene una venta específica
     */
    public function show(Sale $sale, ?array $include = null): Sale
    {
        $defaultIncludes = ['user', 'items.item.product', 'items.item.tax'];

        if ($include) {
            $sale->load(array_merge($defaultIncludes, $include));
        } else {
            $sale->load($defaultIncludes);
        }

        return $sale;
    }

    /**
     * Elimina una venta (operación no permitida)
     */
    public function delete(Sale $sale): void
    {
        throw new Exception('OPERATION_NOT_ALLOWED');
    }
}
