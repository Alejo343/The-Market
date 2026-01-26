<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::query();

        // Filtrar por categoría
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Filtrar por marca
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        // Filtrar por tipo de venta
        if ($request->filled('sale_type')) {
            $query->where('sale_type', $request->input('sale_type'));
        }

        // Filtrar solo activos
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Búsqueda por nombre
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        // Incluir relaciones si se solicitan
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $products = $query->orderBy('name')->get();

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        // Cargar relaciones para la respuesta
        $product->load(['category', 'brand']);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Product $product): ProductResource
    {
        // Cargar relaciones si se solicitan
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $product->load($includes);
        } else {
            // Por defecto cargar categoría y marca
            $product->load(['category', 'brand']);
        }

        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        $product->load(['category', 'brand']);

        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        // Verificar si tiene variantes
        if ($product->variants()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un producto que tiene variantes asociadas',
                'error' => 'product_has_variants'
            ], 422);
        }

        // Verificar si tiene lotes de peso
        if ($product->weightLots()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un producto que tiene lotes de peso asociados',
                'error' => 'product_has_weight_lots'
            ], 422);
        }

        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado exitosamente'
        ], 200);
    }
}
