<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $products = $this->service->list(
            categoryId: $request->filled('category_id')
                ? $request->integer('category_id')
                : null,
            brandId: $request->filled('brand_id')
                ? $request->integer('brand_id')
                : null,
            regionId: $request->filled('region_id')
                ? $request->integer('region_id')
                : null,
            saleType: $request->filled('sale_type')
                ? $request->input('sale_type')
                : null,
            activeOnly: $request->boolean('active_only'),
            search: $request->filled('search')
                ? $request->input('search')
                : null,
            include: $request->has('include')
                ? explode(',', $request->input('include'))
                : null
        );

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->service->create(
            $request->validated()
        );

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Product $product): ProductResource
    {
        $include = $request->has('include')
            ? explode(',', $request->input('include'))
            : null;

        return new ProductResource(
            $this->service->show($product, $include)
        );
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        return new ProductResource(
            $this->service->update(
                $product,
                $request->validated()
            )
        );
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'products'              => ['required', 'array', 'min:1'],
            'products.*.id'          => ['required', 'integer'],
            'products.*.name'        => ['sometimes', 'string', 'max:255'],
            'products.*.description' => ['sometimes', 'nullable', 'string'],
            'products.*.price'       => ['sometimes', 'numeric', 'min:0'],
        ], [
            'products.required'           => 'La lista de productos es obligatoria',
            'products.array'              => 'El campo productos debe ser un arreglo',
            'products.min'                => 'Debe enviar al menos un producto',
            'products.*.id.required'      => 'Cada producto debe tener un ID',
            'products.*.id.integer'       => 'El ID de cada producto debe ser un número entero',
            'products.*.name.string'      => 'El nombre debe ser texto',
            'products.*.name.max'         => 'El nombre no puede exceder 255 caracteres',
            'products.*.price.numeric'    => 'El precio debe ser un número',
            'products.*.price.min'        => 'El precio no puede ser negativo',
        ]);

        $result = $this->service->bulkUpdate($request->input('products'));

        return response()->json([
            'message' => 'Actualización masiva completada',
            'updated_count' => count($result['updated']),
            'updated_ids'   => $result['updated'],
            'errors'        => $result['errors'],
        ], empty($result['errors']) ? 200 : 207);
    }

    public function destroy(Product $product): JsonResponse
    {
        try {
            $this->service->delete($product);

            return response()->json([
                'message' => 'Producto eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => match ($e->getMessage()) {
                    'PRODUCT_HAS_VARIANTS' =>
                    'No se puede eliminar un producto que tiene variantes asociadas',
                    'PRODUCT_HAS_WEIGHT_LOTS' =>
                    'No se puede eliminar un producto que tiene lotes de peso asociados',
                    default => 'Error inesperado'
                }
            ], 422);
        }
    }
}
