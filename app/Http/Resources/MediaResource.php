<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'path' => $this->path,
            'url' => $this->url, // Atributo calculado del modelo
            'type' => $this->type,
            'type_label' => $this->type_label, // Atributo calculado del modelo
            'alt' => $this->alt,
            'size' => $this->size,
            'formatted_size' => $this->formatted_size, // Atributo calculado
            'extension' => $this->extension, // Atributo calculado
            'is_image' => $this->isImage(),

            // Info del pivote cuando viene de relación con Product
            'pivot' => $this->when(
                isset($this->pivot),
                [
                    'is_primary' => $this->pivot->is_primary ?? false,
                    'order' => $this->pivot->order ?? 0,
                ]
            ),

            // Productos asociados (opcional)
            'products' => ProductResource::collection($this->whenLoaded('products')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
