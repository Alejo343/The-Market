<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService $service
    ) {}

    /**
     * Lista todos los medias
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $media = $this->service->list(
            type: $request->filled('type')
                ? $request->input('type')
                : null,
            include: $request->has('include')
                ? explode(',', $request->input('include'))
                : null
        );

        return MediaResource::collection($media);
    }

    /**
     * Sube un archivo genérico
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $media = $this->service->uploadImage(
            $request->file('file'),
            $request->input('type'),
            $request->input('alt')
        );

        return (new MediaResource($media))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Muestra un media específico
     */
    public function show(Request $request, Media $media): MediaResource
    {
        $include = $request->has('include')
            ? explode(',', $request->input('include'))
            : null;

        return new MediaResource(
            $this->service->show($media, $include)
        );
    }

    /**
     * Actualiza un media
     */
    public function update(UpdateMediaRequest $request, Media $media): MediaResource
    {
        return new MediaResource(
            $this->service->update($media, $request->validated())
        );
    }

    /**
     * Elimina un media
     */
    public function destroy(Media $media): JsonResponse
    {
        try {
            $this->service->delete($media);

            return response()->json([
                'message' => 'Media eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => match ($e->getMessage()) {
                    'MEDIA_IN_USE' =>
                    'No se puede eliminar un media que está siendo usado',
                    default => 'Error al eliminar el media: ' . $e->getMessage()
                }
            ], 422);
        }
    }
}
