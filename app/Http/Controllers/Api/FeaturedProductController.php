<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Region;
use App\Services\FeaturedProductService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeaturedProductController extends Controller
{
    public function __construct(
        protected FeaturedProductService $service
    ) {}

    /**
     * GET /featured-products?region_id=X
     * Endpoint público para el ecommerce.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id',
        ]);

        $region = Region::findOrFail($request->integer('region_id'));

        $products = $this->service->getForRegion($region);

        return ProductResource::collection($products);
    }

    /**
     * GET /regions/{region}/featured
     * Lista los destacados de una región (admin).
     */
    public function show(Region $region): AnonymousResourceCollection
    {
        $products = $this->service->getForRegion($region);

        return ProductResource::collection($products);
    }

    /**
     * POST /regions/{region}/featured
     * Sincroniza los productos destacados de una región.
     * Body: { "product_ids": [1, 2, 3, ...] }
     */
    public function sync(Request $request, Region $region): JsonResponse
    {
        $request->validate([
            'product_ids'   => 'present|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        try {
            $this->service->sync($region, $request->input('product_ids', []));

            return response()->json([
                'message' => 'Productos destacados actualizados exitosamente',
                'count'   => $this->service->getCount($region),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => match ($e->getMessage()) {
                    'FEATURED_MIN'              => 'Debes seleccionar al menos ' . FeaturedProductService::MIN . ' productos destacados',
                    'FEATURED_MAX'              => 'No puedes seleccionar más de ' . FeaturedProductService::MAX . ' productos destacados',
                    'FEATURED_INVALID_PRODUCTS' => 'Uno o más productos no son válidos o no están activos',
                    default                     => 'Error inesperado',
                },
            ], 422);
        }
    }
}
