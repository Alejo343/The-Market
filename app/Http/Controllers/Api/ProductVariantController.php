<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\ProductVariant;
use App\Services\ProductVariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class ProductVariantController extends Controller
{
    public function __construct(
        protected ProductVariantService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $variants = $this->service->list(
            productId: $request->filled('product_id')
                ? $request->integer('product_id')
                : null,
            lowStockOnly: $request->boolean('low_stock_only'),
            outOfStockOnly: $request->boolean('out_of_stock_only'),
            inStockOnly: $request->boolean('in_stock_only'),
            onSaleOnly: $request->boolean('on_sale_only'),
            search: $request->filled('search')
                ? $request->input('search')
                : null,
            include: $request->has('include')
                ? explode(',', $request->input('include'))
                : null
        );

        return ProductVariantResource::collection($variants);
    }

    public function store(StoreProductVariantRequest $request): JsonResponse
    {
        try {
            $variant = $this->service->create(
                $request->validated()
            );

            return (new ProductVariantResource($variant))
                ->response()
                ->setStatusCode(201);
        } catch (Exception $e) {
            if ($e->getMessage() === 'INVALID_PRODUCT_TYPE') {
                return response()->json([
                    'message' => 'Solo se pueden crear variantes para productos de venta por unidad',
                    'error' => 'invalid_product_type'
                ], 422);
            }

            throw $e;
        }
    }

    public function show(Request $request, ProductVariant $productVariant): ProductVariantResource
    {
        $include = $request->has('include')
            ? explode(',', $request->input('include'))
            : null;

        return new ProductVariantResource(
            $this->service->show($productVariant, $include)
        );
    }

    public function update(UpdateProductVariantRequest $request, ProductVariant $productVariant): ProductVariantResource
    {
        return new ProductVariantResource(
            $this->service->update(
                $productVariant,
                $request->validated()
            )
        );
    }

    public function destroy(ProductVariant $productVariant): JsonResponse
    {
        try {
            $this->service->delete($productVariant);

            return response()->json([
                'message' => 'Variante eliminada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => match ($e->getMessage()) {
                    'VARIANT_HAS_SALES' =>
                    'No se puede eliminar una variante que tiene ventas asociadas',
                    default => 'Error inesperado'
                }
            ], 422);
        }
    }

    public function findByBarcode(Request $request): ProductVariantResource|JsonResponse
    {
        $request->validate([
            'barcode' => ['required', 'string'],
        ], [
            'barcode.required' => 'El código de barras es obligatorio',
        ]);

        $variant = $this->service->findByBarcode($request->input('barcode'));

        if (!$variant) {
            return response()->json([
                'message' => 'Producto no encontrado con ese código de barras',
                'error' => 'product_not_found'
            ], 404);
        }

        return new ProductVariantResource($variant);
    }
}
