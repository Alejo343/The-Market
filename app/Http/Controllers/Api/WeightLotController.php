<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
                    'LOT_HAS_SALES' =>
                    'No se puede eliminar un lote que tiene ventas asociadas',
                    default => 'Error inesperado'
                }
            ], 422);
        }
    }
}
