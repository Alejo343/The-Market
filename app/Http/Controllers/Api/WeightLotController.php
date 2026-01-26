<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeightLotRequest;
use App\Http\Requests\UpdateWeightLotRequest;
use App\Http\Resources\WeightLotResource;
use App\Models\Product;
use App\Models\WeightLot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WeightLotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = WeightLot::query();

        // Filtrar por producto
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        // Filtrar solo activos
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Filtrar solo con peso disponible
        if ($request->boolean('available_only')) {
            $query->available();
        }

        // Filtrar vencidos
        if ($request->boolean('expired_only')) {
            $query->expired();
        }

        // Filtrar próximos a vencer
        if ($request->boolean('expiring_soon')) {
            $query->expiringSoon();
        }

        // Incluir relaciones si se solicitan
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $lots = $query->orderBy('created_at', 'desc')->get();

        return WeightLotResource::collection($lots);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWeightLotRequest $request): JsonResponse
    {
        // Verificar que el producto sea de tipo 'weight'
        $product = Product::findOrFail($request->input('product_id'));

        if ($product->sale_type !== 'weight') {
            return response()->json([
                'message' => 'Solo se pueden crear lotes para productos de venta por peso',
                'error' => 'invalid_product_type'
            ], 422);
        }

        $lot = WeightLot::create($request->validated());

        $lot->load('product');

        return (new WeightLotResource($lot))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, WeightLot $weightLot): WeightLotResource
    {
        // Cargar relaciones si se solicitan
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $weightLot->load($includes);
        } else {
            $weightLot->load('product');
        }

        return new WeightLotResource($weightLot);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWeightLotRequest $request, WeightLot $weightLot): WeightLotResource
    {
        $weightLot->update($request->validated());

        $weightLot->load('product');

        return new WeightLotResource($weightLot);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WeightLot $weightLot): JsonResponse
    {
        // Verificar si tiene ventas asociadas
        if ($weightLot->saleItems()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un lote que tiene ventas asociadas',
                'error' => 'lot_has_sales'
            ], 422);
        }

        $weightLot->delete();

        return response()->json([
            'message' => 'Lote eliminado exitosamente'
        ], 200);
    }
}
