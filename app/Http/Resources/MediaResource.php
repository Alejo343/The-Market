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
            'url' => $this->url,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'alt' => $this->alt,
            'extension' => $this->extension,
            'size' => $this->size,
            'formatted_size' => $this->formatted_size,
            'is_image' => $this->isImage(),

            // Si está en pivot (relación con producto)
            'is_primary' => $this->whenPivotLoaded('product_media', function () {
                return $this->pivot->is_primary;
            }),
            'order' => $this->whenPivotLoaded('product_media', function () {
                return $this->pivot->order;
            }),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
