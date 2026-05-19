<?php

use App\Models\Sale;
use Livewire\Volt\Component;

new class extends Component {
    public int $lastSaleId = 0;

    public function mount(): void
    {
        $this->lastSaleId = Sale::max('id') ?? 0;
    }

    public function checkNewSales(): void
    {
        $newSales = Sale::where('id', '>', $this->lastSaleId)->with('items.item.product')->latest()->get();

        if ($newSales->isEmpty()) {
            return;
        }

        foreach ($newSales as $sale) {
            $itemsCount = $sale->items->count();
            $total = number_format($sale->total, 0, ',', '.');
            $channel = $sale->channel === 'online' ? 'Online' : 'Tienda';

            $this->dispatch('notify-sale', [
                'title' => "Nueva venta {$channel}",
                'body' => "{$itemsCount} producto(s) — \${$total}",
                'id' => $sale->id,
            ]);
        }

        $this->lastSaleId = $newSales->max('id');
    }
};
?>

<div wire:poll.5s="checkNewSales" x-data
    @notify-sale.window="
         if (Notification.permission === 'granted') {
             new Notification($event.detail[0].title, {
                 body: $event.detail[0].body,
                 icon: '/favicon.ico',
             });
         }
     ">
</div>
