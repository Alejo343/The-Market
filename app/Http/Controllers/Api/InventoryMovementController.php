<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryMovementRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Models\InventoryMovement;
use App\Services\InventoryMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class InventoryMovementController extends Controller
{
    public function __construct(
        protected InventoryMovementService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $movements = $this->service->list(
            type: $request->filled('type')
                ? $request->input('type')
                : null,
            userId: $request->filled('user_id')
                ? $request->integer('user_id')
                : null,
            itemType: $request->filled('item_type')
                ? $request->input('item_type')
                : null,
            itemId: $request->filled('item_id')
                ? $request->integer('item_id')
                : null,
            date: $request->filled('date')
                ? $request->input('date')
                : null,
            startDate: $request->filled('start_date')
                ? $request->input('start_date')
                : null,
            endDate: $request->filled('end_date')
                ? $request->input('end_date')
                : null,
            today: $request->boolean('today')
        );

        return InventoryMovementResource::collection($movements);
    }

    public function store(StoreInventoryMovementRequest $request): JsonResponse
    {
        try {
            $movement = $this->service->create(
                $request->validated(),
                $request->user()->id
            );

            return (new InventoryMovementResource($movement))
                ->response()
                ->setStatusCode(201);
        } catch (Exception $e) {
            $message = match ($e->getMessage()) {
                'INSUFFICIENT_STOCK' =>
                'Stock insuficiente para realizar la salida',
                'INSUFFICIENT_WEIGHT' =>
                'Peso insuficiente para realizar la salida',
                'INVALID_MOVEMENT_TYPE' =>
                'Tipo de movimiento inválido',
                default => 'Error al procesar el movimiento: ' . $e->getMessage()
            };

            return response()->json([
                'message' => $message,
                'error' => 'movement_processing_error'
            ], 422);
        }
    }

    public function show(InventoryMovement $inventoryMovement): InventoryMovementResource
    {
        return new InventoryMovementResource(
            $this->service->show($inventoryMovement)
        );
    }

    public function destroy(InventoryMovement $inventoryMovement): JsonResponse
    {
        try {
            $this->service->delete($inventoryMovement);

            return response()->json([
                'message' => 'Movimiento eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => match ($e->getMessage()) {
                    'OPERATION_NOT_ALLOWED' =>
                    'No se permite eliminar movimientos de inventario. Contacte al administrador.',
                    default => 'Error inesperado'
                },
                'error' => 'operation_not_allowed'
            ], 403);
        }
    }
}
