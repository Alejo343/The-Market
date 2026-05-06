<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SiigoSyncLog;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SiigoSyncService
{
    public function __construct(private SiigoAuthService $auth) {}

    /**
     * Importa todos los productos desde Siigo paginando la API.
     * Retorna array con contadores: created, updated, skipped, errors.
     */
    public function importAll(): array
    {
        $batchId  = (string) Str::uuid();
        $counters = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0, 'batch_id' => $batchId];
        $page     = 1;

        do {
            $response = Http::withHeaders($this->auth->headers())
                ->get(config('services.siigo.api_url') . '/v1/products', [
                    'page'      => $page,
                    'page_size' => 100,
                ]);

            if (! $response->successful()) {
                if ($response->status() === 401) {
                    $this->auth->forgetToken();
                    $response = Http::withHeaders($this->auth->headers())
                        ->get(config('services.siigo.api_url') . '/v1/products', [
                            'page'      => $page,
                            'page_size' => 100,
                        ]);
                }

                if (! $response->successful()) {
                    SiigoSyncLog::record('import', 'error', 'Error listando productos: ' . $response->body(), 'products.list', batchId: $batchId);
                    break;
                }
            }

            $body    = $response->json();
            $results = $body['results'] ?? $body;

            if (empty($results) || ! is_array($results)) {
                break;
            }

            foreach ($results as $item) {
                try {
                    $action = $this->syncProduct($item);
                    $counters[$action]++;
                } catch (Throwable $e) {
                    $counters['errors']++;
                    Log::error('SiigoSync import error', ['code' => $item['code'] ?? '?', 'error' => $e->getMessage()]);
                    SiigoSyncLog::record(
                        'import',
                        'error',
                        'Error sincronizando ' . ($item['code'] ?? '?') . ': ' . $e->getMessage(),
                        'products.import',
                        $item['code'] ?? null,
                        $item['id'] ?? null,
                        $item,
                        $batchId,
                    );
                }
            }

            $hasMore = count($results) >= 100 && isset($body['results']);
            $page++;
        } while ($hasMore);

        return $counters;
    }

    /**
     * Procesa un payload de webhook o polling.
     */
    public function syncFromPayload(array $payload): void
    {
        $topic = $payload['topic'] ?? 'unknown';

        try {
            $action = $this->syncProduct($payload);

            SiigoSyncLog::record(
                'webhook',
                'success',
                "Producto {$action}: " . ($payload['code'] ?? '?'),
                $topic,
                $payload['code'] ?? null,
                $payload['id'] ?? null,
            );
        } catch (Throwable $e) {
            Log::error('SiigoSync webhook error', ['payload' => $payload, 'error' => $e->getMessage()]);
            SiigoSyncLog::record(
                'webhook',
                'error',
                $e->getMessage(),
                $topic,
                $payload['code'] ?? null,
                $payload['id'] ?? null,
                $payload,
            );
        }
    }

    /**
     * Sincroniza un producto Siigo con el sistema local.
     * Retorna 'created', 'updated' o 'skipped'.
     */
    public function syncProduct(array $data): string
    {
        $siigoCode = $data['code'] ?? null;
        $siigoId   = $data['id'] ?? null;

        if (! $siigoCode) {
            return 'skipped';
        }

        // Solo productos físicos — excluir servicios
        $type = $data['type'] ?? 'Product';
        if (in_array($type, ['Service', 'ConsumerGood'])) {
            return 'skipped';
        }

        // solo productos físicos — excluir servicios
        $name = $data['name'] ?? '';
        if (stripos($name, 'SERVICIOS LOGISTICOS') === 0 || stripos($name, 'SEVICIOS LOGISTICOS') === 0) {
            return 'skipped';
        }

        return DB::transaction(function () use ($data, $siigoCode, $siigoId) {
            $variant = ProductVariant::where('sku', $siigoCode)
                ->orWhere('siigo_id', $siigoId)
                ->first();

            if ($variant) {
                return $this->updateVariant($variant, $data);
            }

            // Solo crear si el producto está activo en Siigo
            if (isset($data['active']) && $data['active'] === false) {
                return 'skipped';
            }

            $this->createProduct($data, $siigoCode, $siigoId);
            return 'created';
        });
    }

    private function updateVariant(ProductVariant $variant, array $data): string
    {
        $changed = false;

        // Actualizar siigo_id si faltaba y no está tomado por otra variante
        if (! $variant->siigo_id && isset($data['id'])) {
            $taken = ProductVariant::where('siigo_id', $data['id'])
                ->where('id', '!=', $variant->id)
                ->exists();
            if (! $taken) {
                $variant->siigo_id = $data['id'];
                $changed = true;
            }
        }

        // Stock: usa available_quantity del payload, mínimo 0
        $newStock = $data['available_quantity'] ?? null;
        if ($newStock !== null && $variant->stock !== max(0, (int) $newStock)) {
            $variant->stock = max(0, (int) $newStock);
            $changed = true;
        }

        // Precio: primera lista de precios disponible
        $price = $this->extractPrice($data);
        if ($price !== null && (float) $variant->price !== (float) $price) {
            $variant->price = $price;
            $changed = true;
        }

        if ($changed) {
            $variant->save();
        }

        // Actualizar Product.active y Product.name si cambiaron
        $product = $variant->product;
        $productChanged = false;

        if (isset($data['active']) && $product->active !== (bool) $data['active']) {
            $product->active = (bool) $data['active'];
            $productChanged = true;
        }

        if (isset($data['name']) && $product->name !== $data['name']) {
            $product->name = $data['name'];
            $productChanged = true;
        }

        if ($productChanged) {
            $product->save();
        }

        return ($changed || $productChanged) ? 'updated' : 'skipped';
    }

    private function defaultCategory(): int
    {
        return \App\Models\Category::firstOrCreate(['name' => 'Siigo'])->id;
    }

    private function createProduct(array $data, string $siigoCode, ?string $siigoId): void
    {
        $product = Product::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'sale_type'   => 'unit',
            'active'      => false,
            'category_id' => $this->defaultCategory(),
        ]);

        $tax = $this->resolveTax($data);

        ProductVariant::create([
            'product_id'   => $product->id,
            'presentation' => $data['unit_label'] ?? ($data['unit']['name'] ?? 'Unidad'),
            'sku'          => $siigoCode,
            'siigo_id'     => $siigoId,
            'price'        => $this->extractPrice($data) ?? 0,
            'stock'        => max(0, (int) ($data['available_quantity'] ?? 0)),
            'min_stock'    => 0,
            'tax_id'       => $tax?->id ?? Tax::first()?->id,
        ]);
    }

    private function extractPrice(array $data): ?float
    {
        $prices = $data['prices'] ?? [];
        foreach ($prices as $currency) {
            $list = $currency['price_list'] ?? [];
            if (! empty($list)) {
                return (float) ($list[0]['value'] ?? 0);
            }
        }
        return null;
    }

    private function resolveTax(array $data): ?Tax
    {
        $taxes = $data['taxes'] ?? [];
        if (empty($taxes)) {
            return null;
        }
        // Buscar por porcentaje si coincide
        $percentage = $taxes[0]['percentage'] ?? null;
        if ($percentage !== null) {
            return Tax::where('percentage', $percentage)->first();
        }
        return null;
    }
}
