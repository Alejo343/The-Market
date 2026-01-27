<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Services\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class BrandController extends Controller
{
    public function __construct(
        protected BrandService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $brands = $this->service->list(
            include: $request->has('include')
                ? explode(',', $request->input('include'))
                : null,
            search: $request->filled('search')
                ? $request->input('search')
                : null
        );

        return BrandResource::collection($brands);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $brand = $this->service->create(
            $request->validated()
        );

        return (new BrandResource($brand))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Brand $brand): BrandResource
    {
        $include = $request->has('include')
            ? explode(',', $request->input('include'))
            : null;

        return new BrandResource(
            $this->service->show($brand, $include)
        );
    }

    public function update(UpdateBrandRequest $request, Brand $brand): BrandResource
    {
        return new BrandResource(
            $this->service->update(
                $brand,
                $request->validated()
            )
        );
    }

    public function destroy(Brand $brand): JsonResponse
    {
        try {
            $this->service->delete($brand);

            return response()->json([
                'message' => 'Marca eliminada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => match ($e->getMessage()) {
                    'BRAND_HAS_PRODUCTS' =>
                    'No se puede eliminar una marca que tiene productos asociados',
                    default => 'Error inesperado'
                }
            ], 422);
        }
    }
}
