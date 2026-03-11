<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReduceWeightRequest;
use App\Http\Requests\StoreWeightLotRequest;
use App\Http\Requests\UpdateWeightLotRequest;
use App\Http\Resources\WeightLotResource;
use App\Models\WeightLot;
use App\Services\WeightLotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class WeightLotController extends Controller
{
    public function __construct(
        protected WeightLotService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $lots = $this->service->list(
            productId: $request->filled('product_id')
                ? $request->integer('product_id')
                : null,
            activeOnly: $request->boolean('active_only'),
            availableOnly: $request->boolean('available_only'),
            expiredOnly: $request->boolean('expired_only'),
            expiringSoon: $request->boolean('expiring_soon'),
            search: $request->filled('search')
                ? $request->input('search')
                : null,
            include: $request->has('include')
                ? explode(',', $request->input('include'))
                : null
        );

        return WeightLotResource::collection($lots);
    }

    public function store(StoreWeightLotRequest $request): JsonResponse
    {
        try {
            $lot = $this->service->create(
                $request->validated()
            );

            return (new WeightLotResource($lot))
                ->response()
                ->setStatusCode(201);
        } catch (Exception $e) {
            if ($e->getMessage() === 'INVALID_PRODUCT_TYPE') {
                return response()->json([
                    'message' => 'Solo se pueden crear lotes para productos de venta por peso',
                    'error' => 'invalid_product_type'
                ], 422);
            }

            throw $e;
        }
    }

    public function show(Request $request, WeightLot $weightLot): WeightLotResource
    {
        $include = $request->has('include')
            ? explode(',', $request->input('include'))
            : null;

        return new WeightLotResource(
            $this->service->show($weightLot, $include)
        );
    }

    public function update(UpdateWeightLotRequest $request, WeightLot $weightLot): WeightLotResource
    {
        return new WeightLotResource(
            $this->service->update(
                $weightLot,
                $request->validated()
            )
        );
    }

    public function reduceWeight(ReduceWeightRequest $request, WeightLot $weightLot): JsonResponse
    {
        try {
            $updatedLot = $this->service->reduceWeight(
                $weightLot,
                $request->validated('weight')
            );

            return (new WeightLotResource($updatedLot))
                ->response()
                ->setStatusCode(200);
        } catch (Exception $e) {
            return response()->json([
                'message' => match ($e->getMessage()) {
                    'WEIGHT_LOT_INACTIVE' =>
                    'El lote de peso no está activo',
                    'WEIGHT_LOT_EXPIRED' =>
                    'El lote de peso está vencido',
                    'INSUFFICIENT_WEIGHT' =>
                    'No hay suficiente peso disponible en el lote',
                    default => 'Error inesperado al reducir el peso'
                }
            ], 422);
        }
    }

    public function destroy(WeightLot $weightLot): JsonResponse
    {
        try {
            $this->service->delete($weightLot);

            return response()->json([
                'message' => 'Lote eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => match ($e->getMessage()) {
                    'WEIGHT_LOT_HAS_SALES' =>
                    'No se puede eliminar un lote que tiene ventas asociadas',
                    'WEIGHT_LOT_HAS_MOVEMENTS' =>
                    'No se puede eliminar un lote que tiene movimientos de inventario',
                    default => 'Error inesperado'
                }
            ], 422);
        }
    }
}
