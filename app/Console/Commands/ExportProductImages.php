<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportProductImages extends Command
{
    protected $signature = 'products:export-images
                            {--out= : Carpeta de salida (default: storage/app/exports/product-images)}
                            {--region= : Filtrar por nombre de región}
                            {--only-active : Solo productos activos}';

    protected $description = 'Exporta imágenes de productos organizadas por región. Cada imagen se renombra con SKU + nombre del producto.';

    public function handle(): int
    {
        $outBase = $this->option('out')
            ? rtrim($this->option('out'), '/\\')
            : storage_path('app/exports/product-images');

        $query = Product::with(['region', 'media', 'variants'])
            ->when($this->option('only-active'), fn ($q) => $q->active())
            ->when($this->option('region'), fn ($q) => $q->whereHas(
                'region',
                fn ($r) => $r->where('name', 'like', '%'.$this->option('region').'%')
            ));

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->warn('No se encontraron productos.');
            return self::SUCCESS;
        }

        $this->info("Exportando imágenes de {$products->count()} producto(s) a: {$outBase}");
        $this->newLine();

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $copied = 0;
        $skipped = 0;
        $errors = [];

        foreach ($products as $product) {
            $regionName = $product->region?->name ?? 'Sin-Region';
            $regionFolder = $outBase.DIRECTORY_SEPARATOR.$this->sanitize($regionName);

            if (!is_dir($regionFolder)) {
                mkdir($regionFolder, 0755, true);
            }

            // SKU: primer variant, o todos separados por guion si hay varios
            $skus = $product->variants->pluck('sku')->filter()->values();
            $sku = $skus->isNotEmpty()
                ? $skus->implode('-')
                : 'sin-sku';

            $productSlug = $this->sanitize($product->name);

            foreach ($product->media as $index => $media) {
                $sourcePath = Storage::disk('public')->path($media->path);

                if (!file_exists($sourcePath)) {
                    $errors[] = "No encontrado: {$media->path} (Producto: {$product->name})";
                    $skipped++;
                    continue;
                }

                $ext = strtolower(pathinfo($media->path, PATHINFO_EXTENSION));
                $suffix = $product->media->count() > 1 ? "-{$index}" : '';
                $destName = "{$sku}_{$productSlug}{$suffix}.{$ext}";
                $destPath = $regionFolder.DIRECTORY_SEPARATOR.$destName;

                if (copy($sourcePath, $destPath)) {
                    $copied++;
                } else {
                    $errors[] = "Error copiando: {$media->path}";
                    $skipped++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Imágenes copiadas: {$copied}");
        if ($skipped > 0) {
            $this->warn("⚠ Omitidas/errores: {$skipped}");
            foreach ($errors as $err) {
                $this->line("  - {$err}");
            }
        }

        $this->newLine();
        $this->line("Carpeta de salida: <fg=cyan>{$outBase}</>");

        return self::SUCCESS;
    }

    private function sanitize(string $value): string
    {
        // Transliterate accented chars, then strip anything not alphanumeric/dash/underscore
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^\w\s-]/', '', $value);
        $value = preg_replace('/[\s_-]+/', '-', trim($value));

        return Str::limit($value, 60, '');
    }
}
