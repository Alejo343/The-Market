<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class MediaService
{
    /**
     * Lista medias con filtros opcionales
     */
    public function list(
        ?string $type = null,
        ?array $include = null
    ): Collection {
        $query = Media::query();

        if ($type) {
            $query->ofType($type);
        }

        if ($include) {
            $query->with($include);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Sube una imagen genérica (método base reutilizable)
     */
    public function uploadImage(
        UploadedFile $file,
        string $type = Media::TYPE_OTHER,
        ?string $alt = null,
        ?callable $afterCreate = null
    ): Media {
        // Validar tipo
        if (!in_array($type, Media::getTypes())) {
            throw new \InvalidArgumentException('Tipo de media no válido');
        }

        DB::beginTransaction();

        $tmpPath = null;

        try {
            $filename = $file->getClientOriginalName();

            // Process: center by visual mass, square canvas, convert to WebP
            $processor = app(ImageProcessingService::class);
            $tmpPath = $processor->process($file);

            $folder = match ($type) {
                Media::TYPE_PRODUCT => 'products',
                Media::TYPE_CATEGORY => 'categories',
                Media::TYPE_PROMOTION => 'promotions',
                Media::TYPE_BRAND => 'brands',
                Media::TYPE_USER => 'users',
                default => 'other',
            };

            if ($tmpPath) {
                // Processed raster image → store as WebP
                $uniqueName = Str::uuid() . '.webp';
                $path = Storage::disk('public')->putFileAs($folder, new File($tmpPath), $uniqueName);
                $size = filesize($tmpPath);
            } else {
                // Non-raster (e.g. SVG) → store as-is
                $extension = $file->getClientOriginalExtension();
                $uniqueName = Str::uuid() . '.' . $extension;
                $path = $file->storeAs($folder, $uniqueName, 'public');
                $size = $file->getSize();
            }

            $media = Media::create([
                'filename' => $filename,
                'path' => $path,
                'type' => $type,
                'alt' => $alt,
                'size' => $size,
            ]);

            if ($afterCreate) {
                $afterCreate($media);
            }

            DB::commit();

            return $media->fresh();
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        } finally {
            if ($tmpPath && file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }
    }

    /**
     * Sube una imagen y la asocia a un producto
     */
    public function uploadProductImage(
        Product $product,
        UploadedFile $file,
        ?string $alt = null,
        bool $isPrimary = false,
        ?int $order = null
    ): Media {
        return $this->uploadImage(
            $file,
            Media::TYPE_PRODUCT,
            $alt ?? $product->name,
            function ($media) use ($product, $isPrimary, $order) {
                // Si es imagen principal, desmarcar las demás
                if ($isPrimary) {
                    $product->media()->updateExistingPivot(
                        $product->media()->pluck('media.id'),
                        ['is_primary' => false]
                    );
                }

                // Si no se especifica orden, usar el siguiente disponible
                if ($order === null) {
                    $order = $product->media()->count();
                }

                // Asociar al producto
                $product->media()->attach($media->id, [
                    'is_primary' => $isPrimary,
                    'order' => $order,
                ]);
            }
        );
    }

    /**
     * Sube múltiples imágenes de producto
     */
    public function uploadMultipleProductImages(
        Product $product,
        array $files,
        ?array $alts = null,
        bool $firstIsPrimary = false
    ): array {
        $uploadedMedia = [];

        foreach ($files as $index => $file) {
            $alt = $alts[$index] ?? null;
            $isPrimary = $firstIsPrimary && $index === 0;

            $uploadedMedia[] = $this->uploadProductImage(
                $product,
                $file,
                $alt,
                $isPrimary,
                $index
            );
        }

        return $uploadedMedia;
    }

    /**
     * Obtiene un media específico
     */
    public function show(Media $media, ?array $include = null): Media
    {
        if ($include) {
            $media->load($include);
        }

        return $media;
    }

    /**
     * Actualiza un media
     */
    public function update(Media $media, array $data): Media
    {
        $media->update($data);
        return $media->fresh();
    }

    /**
     * Actualiza el texto alternativo de una imagen
     */
    public function updateAlt(Media $media, ?string $alt): Media
    {
        return $this->update($media, ['alt' => $alt]);
    }

    /**
     * Actualiza el tipo de una imagen
     */
    public function updateType(Media $media, string $type): Media
    {
        if (!in_array($type, Media::getTypes())) {
            throw new \InvalidArgumentException('Tipo de media no válido');
        }

        return $this->update($media, ['type' => $type]);
    }

    /**
     * Elimina un media
     */
    public function delete(Media $media): void
    {
        // Verificar si está siendo usado
        if ($media->products()->exists()) {
            throw new Exception('MEDIA_IN_USE');
        }

        // Eliminar archivo físico
        $media->deleteFile();

        // Eliminar registro
        $media->delete();
    }

    /**
     * Elimina una imagen del producto
     */
    public function deleteProductImage(Product $product, Media $media): bool
    {
        DB::beginTransaction();

        try {
            $wasPrimary = $product->media()
                ->where('media.id', $media->id)
                ->first()
                ?->pivot
                ?->is_primary;

            // Desasociar del producto
            $product->media()->detach($media->id);

            // Si era la imagen principal, establecer otra como principal
            if ($wasPrimary && $product->media()->count() > 0) {
                $firstMedia = $product->media()->orderByPivot('order')->first();
                if ($firstMedia) {
                    $product->media()->updateExistingPivot($firstMedia->id, [
                        'is_primary' => true
                    ]);
                }
            }

            // Si no está asociado a ningún otro producto, eliminar
            if ($media->products()->count() === 0) {
                $media->deleteFile();
                $media->delete();
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Establece una imagen como principal
     */
    public function setPrimaryImage(Product $product, Media $media): bool
    {
        // Verificar que la imagen pertenece al producto
        if (!$product->media()->where('media.id', $media->id)->exists()) {
            throw new \Exception('Esta imagen no pertenece a este producto');
        }

        DB::beginTransaction();

        try {
            // Desmarcar todas como primarias
            $product->media()->updateExistingPivot(
                $product->media()->pluck('media.id'),
                ['is_primary' => false]
            );

            // Marcar la nueva como primaria
            $product->media()->updateExistingPivot($media->id, [
                'is_primary' => true
            ]);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reordena las imágenes de un producto
     */
    public function reorderImages(Product $product, array $mediaIds): bool
    {
        DB::beginTransaction();

        try {
            foreach ($mediaIds as $order => $mediaId) {
                // Verificar que la imagen pertenece al producto
                if ($product->media()->where('media.id', $mediaId)->exists()) {
                    $product->media()->updateExistingPivot($mediaId, [
                        'order' => $order
                    ]);
                }
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Elimina todas las imágenes de un producto
     */
    public function deleteAllProductImages(Product $product): bool
    {
        DB::beginTransaction();

        try {
            $mediaIds = $product->media()->pluck('media.id');

            foreach ($mediaIds as $mediaId) {
                $media = Media::find($mediaId);
                if ($media) {
                    $this->deleteProductImage($product, $media);
                }
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene medias por tipo
     */
    public function getByType(string $type): Collection
    {
        if (!in_array($type, Media::getTypes())) {
            throw new \InvalidArgumentException('Tipo de media no válido');
        }

        return $this->list(type: $type);
    }

    /**
     * Obtiene imágenes de productos
     */
    public function getProductImages(): Collection
    {
        return $this->list(type: Media::TYPE_PRODUCT);
    }
}
