<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryMovementRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\WeightLot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class InventoryMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = InventoryMovement::with(['user', 'item']);

        // Filtrar por tipo de movimiento
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filtrar por usuario
        if ($request->filled('user_id')) {
            $query->byUser($request->input('user_id'));
        }

        // Filtrar por item_type
        if ($request->filled('item_type')) {
            $itemType = $request->input('item_type') === 'variant'
                ? ProductVariant::class
                : WeightLot::class;
            $query->where('item_type', $itemType);
        }

        // Filtrar por item_id
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->input('item_id'));
        }

        // Filtrar por fecha
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        // Filtrar entre fechas
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->betweenDates(
                $request->input('start_date'),
                $request->input('end_date')
            );
        }

        // Solo movimientos de hoy
        if ($request->boolean('today')) {
            $query->today();
        }

        $movements = $query->orderBy('created_at', 'desc')->get();

        return InventoryMovementResource::collection($movements);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInventoryMovementRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $itemType = $request->input('item_type');
            $itemId = $request->input('item_id');
            $movementType = $request->input('type');
            $quantity = $request->input('quantity');

            // Determinar el modelo correcto
            if ($itemType === 'variant') {
                $item = ProductVariant::findOrFail($itemId);
                $modelClass = ProductVariant::class;

                // Aplicar el movimiento al stock
                if ($movementType === 'in') {
                    $item->increaseStock($quantity);
                } elseif ($movementType === 'out') {
                    if ($item->stock < $quantity) {
                        throw new \Exception('Stock insuficiente para realizar la salida');
                    }
                    $item->decreaseStock($quantity);
                } else {
                    // Ajuste: puede ser positivo o negativo
                    // Si quantity es negativa, es una reducción
                    $item->stock = max(0, $item->stock + $quantity);
                    $item->save();
                }
            } else {
                $item = WeightLot::findOrFail($itemId);
                $modelClass = WeightLot::class;

                // Aplicar el movimiento al peso
                if ($movementType === 'in') {
                    $item->increment('available_weight', $quantity);
                    $item->increment('initial_weight', $quantity);
                } elseif ($movementType === 'out') {
                    if ($item->available_weight < $quantity) {
                        throw new \Exception('Peso insuficiente para realizar la salida');
                    }
                    $item->reduceWeight($quantity);
                } else {
                    // Ajuste
                    $newWeight = max(0, $item->available_weight + $quantity);
                    $item->available_weight = $newWeight;

                    if ($newWeight <= 0) {
                        $item->active = false;
                    }

                    $item->save();
                }
            }

            // Crear el registro del movimiento
            $movement = InventoryMovement::create([
                'item_type' => $modelClass,
                'item_id' => $itemId,
                'type' => $movementType,
                'quantity' => $quantity,
                'user_id' => $request->user()->id,
                'note' => $request->input('note'),
            ]);

            DB::commit();

            $movement->load(['user', 'item']);

            return (new InventoryMovementResource($movement))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al procesar el movimiento: ' . $e->getMessage(),
                'error' => 'movement_processing_error'
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryMovement $inventoryMovement): InventoryMovementResource
    {
        $inventoryMovement->load(['user', 'item.product']);

        return new InventoryMovementResource($inventoryMovement);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryMovement $inventoryMovement): JsonResponse
    {
        return response()->json([
            'message' => 'No se permite eliminar movimientos de inventario. Contacte al administrador.',
            'error' => 'operation_not_allowed'
        ], 403);
    }
}
