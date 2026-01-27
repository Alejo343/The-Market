<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class SaleController extends Controller
{
    public function __construct(
        protected SaleService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $sales = $this->service->list(
            channel: $request->filled('channel')
                ? $request->input('channel')
                : null,
            userId: $request->filled('user_id')
                ? $request->integer('user_id')
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

        return SaleResource::collection($sales);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        try {
            $sale = $this->service->create(
                $request->validated(),
                $request->user()->id
            );

            return (new SaleResource($sale))
                ->response()
                ->setStatusCode(201);
        } catch (Exception $e) {
            $message = match (true) {
                str_starts_with($e->getMessage(), 'INSUFFICIENT_STOCK:') =>
                'Stock insuficiente para ' . str_replace('INSUFFICIENT_STOCK: ', '', $e->getMessage()),
                $e->getMessage() === 'INSUFFICIENT_WEIGHT' =>
                'Peso insuficiente en el lote',
                $e->getMessage() === 'INACTIVE_LOT' =>
                'El lote no está activo',
                default => 'Error al procesar la venta: ' . $e->getMessage()
            };

            return response()->json([
                'message' => $message,
                'error' => 'sale_processing_error'
            ], 422);
        }
    }

    public function show(Sale $sale): SaleResource
    {
        return new SaleResource(
            $this->service->show($sale)
        );
    }

    public function destroy(Sale $sale): JsonResponse
    {
        try {
            $this->service->delete($sale);

            return response()->json([
                'message' => 'Venta eliminada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => match ($e->getMessage()) {
                    'OPERATION_NOT_ALLOWED' =>
                    'No se permite eliminar ventas. Contacte al administrador si necesita anular una venta.',
                    default => 'Error inesperado'
                },
                'error' => 'operation_not_allowed'
            ], 403);
        }
    }
}
