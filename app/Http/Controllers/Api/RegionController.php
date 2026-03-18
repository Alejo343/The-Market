<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProductVariantResource;
use App\Models\ProductVariant;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegionRequest;
use App\Http\Requests\UpdateRegionRequest;
use App\Http\Resources\RegionResource;
use App\Models\Region;
use App\Services\RegionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class RegionController extends Controller
{
    public function __construct(
        protected RegionService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $regions = $this->service->list(
            activeOnly: $request->boolean('active_only'),
            search: $request->filled('search')
                ? $request->input('search')
                : null,
            include: $request->has('include')
                ? explode(',', $request->input('include'))
                : null
        );

        return RegionResource::collection($regions);
    }

    /**
     * Lista todas las variantes de productos pertenecientes a la región.
     */
    public function variants(Request $request, Region $region): AnonymousResourceCollection
    {
        $variants = ProductVariant::whereHas('product', function ($query) use ($region) {
            $query->where('region_id', $region->id);
        })
            ->with(['product.media', 'tax'])
            ->paginate($request->integer('per_page', 15));

        return ProductVariantResource::collection($variants);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRegionRequest $request): JsonResponse
    {
        $region = $this->service->create($request->validated());

        return (new RegionResource($region))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Region $region): RegionResource
    {
        $include = $request->has('include')
            ? explode(',', $request->input('include'))
            : null;

        return new RegionResource(
            $this->service->show($region, $include)
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRegionRequest $request, Region $region): RegionResource
    {
        return new RegionResource(
            $this->service->update($region, $request->validated())
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Region $region): JsonResponse
    {
        try {
            $this->service->delete($region);

            return response()->json([
                'message' => 'Región eliminada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => match ($e->getMessage()) {
                    'REGION_HAS_PRODUCTS' =>
                    'No se puede eliminar una región que tiene productos asociados',
                    default => 'Error inesperado'
                }
            ], 422);
        }
    }
}
