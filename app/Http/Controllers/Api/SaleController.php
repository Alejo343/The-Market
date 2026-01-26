<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\WeightLot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Sale::with(['user', 'items.item']);

        // Filtrar por canal
        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        // Filtrar por usuario
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
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

        // Solo ventas de hoy
        if ($request->boolean('today')) {
            $query->today();
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        return SaleResource::collection($sales);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaleRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $subtotal = 0;
            $taxTotal = 0;
            $saleItems = [];

            // Procesar cada item
            foreach ($request->input('items') as $itemData) {
                $itemType = $itemData['type'];
                $itemId = $itemData['id'];
                $quantity = $itemData['quantity'];

                if ($itemType === 'variant') {
                    // Producto por unidad
                    $variant = ProductVariant::with('tax')->findOrFail($itemId);

                    // Verificar stock
                    if ($variant->stock < $quantity) {
                        throw new \Exception("Stock insuficiente para {$variant->presentation}");
                    }

                    $price = $variant->getFinalPrice();
                    $itemSubtotal = $price * $quantity;
                    $itemTax = $variant->tax->calculateTaxAmount($itemSubtotal);

                    // Reducir stock
                    $variant->decreaseStock($quantity);

                    $saleItems[] = [
                        'item_type' => ProductVariant::class,
                        'item_id' => $variant->id,
                        'quantity' => $quantity,
                        'price' => $price,
                        'subtotal' => $itemSubtotal,
                    ];

                    $subtotal += $itemSubtotal;
                    $taxTotal += $itemTax;
                } else {
                    // Producto por peso (carnicería)
                    $lot = WeightLot::findOrFail($itemId);

                    // Verificar peso disponible
                    if ($lot->available_weight < $quantity) {
                        throw new \Exception("Peso insuficiente en el lote");
                    }

                    // Verificar que esté activo
                    if (!$lot->active) {
                        throw new \Exception("El lote no está activo");
                    }

                    $price = $lot->price_per_kg;
                    $itemSubtotal = $price * $quantity;

                    // Para carnicería, puedes definir un IVA fijo o variable
                    // Aquí asumo 5% para carnes (ajusta según necesites)
                    $itemTax = $itemSubtotal * 0.05;

                    // Reducir peso disponible
                    $lot->reduceWeight($quantity);

                    $saleItems[] = [
                        'item_type' => WeightLot::class,
                        'item_id' => $lot->id,
                        'quantity' => $quantity,
                        'price' => $price,
                        'subtotal' => $itemSubtotal,
                    ];

                    $subtotal += $itemSubtotal;
                    $taxTotal += $itemTax;
                }
            }

            // Crear la venta
            $sale = Sale::create([
                'channel' => $request->input('channel'),
                'user_id' => $request->user()->id,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
            ]);

            // Crear los items de venta
            $sale->items()->createMany($saleItems);

            DB::commit();

            // Cargar relaciones para la respuesta
            $sale->load(['user', 'items.item']);

            return (new SaleResource($sale))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al procesar la venta: ' . $e->getMessage(),
                'error' => 'sale_processing_error'
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale): SaleResource
    {
        $sale->load(['user', 'items.item.product', 'items.item.tax']);

        return new SaleResource($sale);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale): JsonResponse
    {
        return response()->json([
            'message' => 'No se permite eliminar ventas. Contacte al administrador si necesita anular una venta.',
            'error' => 'operation_not_allowed'
        ], 403);
    }
}
