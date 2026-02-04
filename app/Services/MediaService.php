<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
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

        try {
            // Generar nombre único para evitar colisiones
            $filename = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $uniqueName = Str::uuid() . '.' . $extension;

            // Determinar carpeta según tipo
            $folder = match ($type) {
                Media::TYPE_PRODUCT => 'products',
                Media::TYPE_CATEGORY => 'categories',
                Media::TYPE_PROMOTION => 'promotions',
                Media::TYPE_BRAND => 'brands',
                Media::TYPE_USER => 'users',
                default => 'other',
            };

            // Almacenar en carpeta correspondiente
            $path = $file->storeAs($folder, $uniqueName, 'public');

            // Crear registro de media
            $media = Media::create([
                'filename' => $filename,
                'path' => $path,
                'type' => $type,
                'alt' => $alt,
                'size' => $file->getSize(),
            ]);

            // Ejecutar callback después de crear (para asociaciones)
            if ($afterCreate) {
                $afterCreate($media);
            }

            DB::commit();

            return $media->fresh();
        } catch (\Exception $e) {
            DB::rollBack();

            // Eliminar archivo si falló
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }
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
     * Actualiza el texto alternativo de una imagen
     */
    public function updateAlt(Media $media, ?string $alt): Media
    {
        $media->update(['alt' => $alt]);
        return $media->fresh();
    }

    /**
     * Actualiza el tipo de una imagen
     */
    public function updateType(Media $media, string $type): Media
    {
        if (!in_array($type, Media::getTypes())) {
            throw new \InvalidArgumentException('Tipo de media no válido');
        }

        $media->update(['type' => $type]);
        return $media->fresh();
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
     * Obtiene todas las imágenes por tipo
     */
    public function getByType(string $type)
    {
        if (!in_array($type, Media::getTypes())) {
            throw new \InvalidArgumentException('Tipo de media no válido');
        }

        return Media::ofType($type)->get();
    }
}
