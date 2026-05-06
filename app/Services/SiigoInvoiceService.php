<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\Sale;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SiigoInvoiceService
{
    public function __construct(private SiigoAuthService $auth) {}

    public function createFromSale(Sale $sale): ?string
    {
        $sale->loadMissing(['items.item']);

        try {
            $payload = $this->buildPayload($sale);

            $response = Http::withHeaders($this->auth->headers())
                ->post(config('services.siigo.api_url') . '/v1/invoices', $payload);

            if ($response->successful()) {
                $invoiceId = (string) $response->json('id');
                $sale->update(['siigo_invoice_id' => $invoiceId]);
                return $invoiceId;
            }

            Log::error('Siigo invoice creation failed', [
                'sale_id' => $sale->id,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);

            return null;
        } catch (Throwable $e) {
            Log::error('Siigo invoice exception', [
                'sale_id' => $sale->id,
                'error'   => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function buildPayload(Sale $sale): array
    {
        return [
            'document' => [
                'id' => (int) config('services.siigo.invoice_document_id'),
            ],
            'customer' => [
                'identification' => $sale->customer_identification ?? '222222222',
                'branch_office'  => 0,
            ],
            'date'     => $sale->created_at->format('Y-m-d'),
            'items'    => $this->buildItems($sale),
            'payments' => [
                [
                    'id'    => (int) config('services.siigo.payment_type_id'),
                    'value' => (float) $sale->total,
                ],
            ],
        ];
    }

    private function buildItems(Sale $sale): array
    {
        return $sale->items
            ->filter(fn ($item) => $item->item instanceof ProductVariant && $item->item->sku)
            ->map(fn ($item) => [
                'code'        => $item->item->sku,
                'description' => $item->item->presentation ?? $item->item->sku,
                'quantity'    => round((float) $item->quantity, 2),
                'price'       => round((float) $item->price, 2),
            ])
            ->values()
            ->toArray();
    }
}
