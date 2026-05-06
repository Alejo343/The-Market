<?php

namespace App\Jobs;

use App\Models\Sale;
use App\Services\SiigoInvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateSiigoInvoiceJob implements ShouldQueue
{
    use Queueable;

    public int $tries  = 3;
    public int $backoff = 60;

    public function __construct(private int $saleId) {}

    public function handle(SiigoInvoiceService $service): void
    {
        $sale = Sale::find($this->saleId);

        if (! $sale || $sale->siigo_invoice_id) {
            return;
        }

        $service->createFromSale($sale);
    }
}
