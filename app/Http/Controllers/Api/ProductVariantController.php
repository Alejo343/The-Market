<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductVariantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ProductVariant::query();

        // Filtrar por producto
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        // Filtrar solo con stock bajo
        if ($request->boolean('low_stock_only')) {
            $query->lowStock();
        }

        // Filtrar solo sin stock
        if ($request->boolean('out_of_stock_only')) {
            $query->outOfStock();
        }

        // Filtrar solo en stock
        if ($request->boolean('in_stock_only')) {
            $query->inStock();
        }

        // Filtrar solo ofertas
        if ($request->boolean('on_sale_only')) {
            $query->onSale();
        }

        // Búsqueda por presentación o SKU
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('presentation', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Incluir relaciones si se solicitan
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $variants = $query->orderBy('presentation')->get();

        return ProductVariantResource::collection($variants);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductVariantRequest $request): JsonResponse
    {
        // Verificar que el producto sea de tipo 'unit'
        $product = Product::findOrFail($request->input('product_id'));

        if ($product->sale_type !== 'unit') {
            return response()->json([
                'message' => 'Solo se pueden crear variantes para productos de venta por unidad',
                'error' => 'invalid_product_type'
            ], 422);
        }

        $variant = ProductVariant::create($request->validated());

        $variant->load(['product', 'tax']);

        return (new ProductVariantResource($variant))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, ProductVariant $productVariant): ProductVariantResource
    {
        // Cargar relaciones si se solicitan
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $productVariant->load($includes);
        } else {
            $productVariant->load(['product', 'tax']);
        }

        return new ProductVariantResource($productVariant);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductVariantRequest $request, ProductVariant $productVariant): ProductVariantResource
    {
        $productVariant->update($request->validated());

        $productVariant->load(['product', 'tax']);

        return new ProductVariantResource($productVariant);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductVariant $productVariant): JsonResponse
    {
        // Verificar si tiene ventas asociadas
        if ($productVariant->saleItems()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una variante que tiene ventas asociadas',
                'error' => 'variant_has_sales'
            ], 422);
        }

        $productVariant->delete();

        return response()->json([
            'message' => 'Variante eliminada exitosamente'
        ], 200);
    }
}
