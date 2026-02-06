<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadProductImageRequest;
use App\Http\Requests\UploadMultipleProductImagesRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Models\Product;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class ProductMediaController extends Controller
{
    public function __construct(
        protected MediaService $service
    ) {}

    /**
     * Lista las imágenes de un producto
     */
    public function index(Product $product): AnonymousResourceCollection
    {
        return MediaResource::collection($product->media);
    }

    /**
     * Sube una imagen al producto
     */
    public function store(UploadProductImageRequest $request, Product $product): JsonResponse
    {
        $media = $this->service->uploadProductImage(
            $product,
            $request->file('file'),
            $request->input('alt'),
            $request->boolean('is_primary'),
            $request->input('order')
        );

        return (new MediaResource($media))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Sube múltiples imágenes al producto
     */
    public function storeMultiple(UploadMultipleProductImagesRequest $request, Product $product): JsonResponse
    {
        $uploadedMedia = $this->service->uploadMultipleProductImages(
            $product,
            $request->file('files'),
            $request->input('alts'),
            $request->boolean('first_is_primary')
        );

        return response()->json([
            'message' => 'Imágenes subidas exitosamente',
            'data' => MediaResource::collection(collect($uploadedMedia))
        ], 201);
    }

    /**
     * Elimina una imagen del producto
     */
    public function destroy(Product $product, Media $media): JsonResponse
    {
        try {
            $this->service->deleteProductImage($product, $media);

            return response()->json([
                'message' => 'Imagen eliminada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la imagen: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Establece una imagen como principal
     */
    public function setPrimary(Product $product, Media $media): JsonResponse
    {
        try {
            $this->service->setPrimaryImage($product, $media);

            return response()->json([
                'message' => 'Imagen establecida como principal'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reordena las imágenes del producto
     */
    public function reorder(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'media_ids' => ['required', 'array'],
            'media_ids.*' => ['required', 'integer', 'exists:media,id'],
        ], [
            'media_ids.required' => 'Debe proporcionar el orden de las imágenes',
            'media_ids.*.exists' => 'Una o más imágenes no existen',
        ]);

        try {
            $this->service->reorderImages($product, $request->input('media_ids'));

            return response()->json([
                'message' => 'Orden actualizado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al reordenar: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Elimina todas las imágenes del producto
     */
    public function destroyAll(Product $product): JsonResponse
    {
        try {
            $this->service->deleteAllProductImages($product);

            return response()->json([
                'message' => 'Todas las imágenes eliminadas exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar imágenes: ' . $e->getMessage()
            ], 422);
        }
    }
}
