<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\WeightLot;
use Illuminate\Support\Facades\DB;

class OrderInventoryService
{
    public function processApprovedOrder(Order $order): void
    {
        if ($order->status !== 'APPROVED') {
            throw new \Exception('Only approved orders can process inventory');
        }

        $items = $order->items_data;

        try {
            DB::transaction(function () use ($items) {
                foreach ($items as $item) {
                    $this->decrementInventory($item);
                }
            });

            \Log::info("Inventory decremented for order {$order->reference}");
        } catch (\Exception $e) {
            \Log::error("Inventory error for order {$order->reference}: {$e->getMessage()}");
            throw $e;
        }
    }

    public function restoreRejectedOrder(Order $order): void
    {
        if (!in_array($order->status, ['DECLINED', 'VOIDED', 'ERROR'])) {
            throw new \Exception('Only rejected orders can restore inventory');
        }

        $items = $order->items_data;

        try {
            DB::transaction(function () use ($items) {
                foreach ($items as $item) {
                    $this->incrementInventory($item);
                }
            });

            \Log::info("Inventory restored for order {$order->reference}");
        } catch (\Exception $e) {
            \Log::error("Inventory restore error for order {$order->reference}: {$e->getMessage()}");
            throw $e;
        }
    }

    private function decrementInventory(array $item): void
    {
        $variantId = $item['variantId'] ?? null;
        $quantity = (int) ($item['quantity'] ?? 0);

        if (!$variantId || $quantity <= 0) {
            return;
        }

        $variant = ProductVariant::find($variantId);
        if (!$variant) {
            \Log::warning("Product variant {$variantId} not found, skipping inventory decrement");
            return;
        }

        if ($variant->stock < $quantity) {
            throw new \Exception("Insufficient stock for variant {$variantId}");
        }

        $variant->decrement('stock', $quantity);
    }

    private function incrementInventory(array $item): void
    {
        $variantId = $item['variantId'] ?? null;
        $quantity = (int) ($item['quantity'] ?? 0);

        if (!$variantId || $quantity <= 0) {
            return;
        }

        $variant = ProductVariant::find($variantId);
        if (!$variant) {
            \Log::warning("Product variant {$variantId} not found, skipping inventory increment");
            return;
        }

        $variant->increment('stock', $quantity);
    }
}
