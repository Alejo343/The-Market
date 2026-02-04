<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    // Constantes para tipos de media
    public const TYPE_PRODUCT = 'product';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_PROMOTION = 'promotion';
    public const TYPE_BRAND = 'brand';
    public const TYPE_USER = 'user';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'filename',
        'path',
        'type',
        'alt',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Tipos de media válidos
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_PRODUCT,
            self::TYPE_CATEGORY,
            self::TYPE_PROMOTION,
            self::TYPE_BRAND,
            self::TYPE_USER,
            self::TYPE_OTHER,
        ];
    }

    /**
     * Etiquetas de tipos en español
     */
    public static function getTypeLabels(): array
    {
        return [
            self::TYPE_PRODUCT => 'Producto',
            self::TYPE_CATEGORY => 'Categoría',
            self::TYPE_PROMOTION => 'Promoción',
            self::TYPE_BRAND => 'Marca',
            self::TYPE_USER => 'Usuario',
            self::TYPE_OTHER => 'Otro',
        ];
    }

    /**
     * Obtiene la etiqueta del tipo
     */
    public function getTypeLabelAttribute(): string
    {
        return self::getTypeLabels()[$this->type] ?? 'Desconocido';
    }

    /**
     * Productos que usan este media
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_media')
            ->withPivot(['is_primary', 'order'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    /**
     * Obtiene la URL completa del archivo
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Elimina el archivo físico del disco
     */
    public function deleteFile(): bool
    {
        return Storage::disk('public')->delete($this->path);
    }

    /**
     * Verifica si es una imagen
     */
    public function isImage(): bool
    {
        $extension = pathinfo($this->filename, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
    }

    /**
     * Obtiene la extensión del archivo
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * Obtiene el tamaño formateado
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para imágenes de productos
     */
    public function scopeProduct($query)
    {
        return $query->where('type', self::TYPE_PRODUCT);
    }

    /**
     * Scope para imágenes de categorías
     */
    public function scopeCategory($query)
    {
        return $query->where('type', self::TYPE_CATEGORY);
    }

    /**
     * Scope para imágenes de promociones
     */
    public function scopePromotion($query)
    {
        return $query->where('type', self::TYPE_PROMOTION);
    }

    /**
     * Scope para imágenes de marcas
     */
    public function scopeBrand($query)
    {
        return $query->where('type', self::TYPE_BRAND);
    }

    /**
     * Verifica si es imagen de producto
     */
    public function isProductImage(): bool
    {
        return $this->type === self::TYPE_PRODUCT;
    }

    /**
     * Verifica si es imagen de categoría
     */
    public function isCategoryImage(): bool
    {
        return $this->type === self::TYPE_CATEGORY;
    }

    /**
     * Verifica si es imagen de promoción
     */
    public function isPromotionImage(): bool
    {
        return $this->type === self::TYPE_PROMOTION;
    }
}
