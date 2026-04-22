<?php

namespace App\Services;

use App\Jobs\SendWhatsAppJob;
use App\Models\Order;

class OrderService
{
    public function __construct(
        protected OrderInventoryService $inventoryService
    ) {}

    public function createPendingOrder(
        string $reference,
        string $paymentMethod,
        string $customerEmail,
        string $customerName,
        string $customerPhone,
        string $customerAddress,
        string $customerCity,
        array $items,
        int $totalAmountCents,
        string $notes = ''
    ): Order {
        return Order::create([
            'reference' => $reference,
            'payment_method' => $paymentMethod,
            'status' => 'PENDING',
            'customer_email' => $customerEmail,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_address' => $customerAddress,
            'customer_city' => $customerCity,
            'items_data' => $items,
            'total_amount_cents' => $totalAmountCents,
            'notes' => $notes,
        ]);
    }

    public function updateTransactionId(string $reference, string $transactionId): Order
    {
        $order = Order::where('reference', $reference)->firstOrFail();
        $order->update(['transaction_id' => $transactionId]);
        return $order;
    }

    public function updateStatus(string $transactionId, string $status, string $statusMessage = ''): Order
    {
        $order = Order::where('transaction_id', $transactionId)->firstOrFail();
        $oldStatus = $order->status;

        $notes = ($order->notes ?? '') . "\n[" . now()->toIso8601String() . "] Status updated to {$status}";
        if ($statusMessage) {
            $notes .= ": {$statusMessage}";
        }

        $order->update([
            'status' => $status,
            'notes' => trim($notes),
        ]);

        // Handle inventory changes
        if ($oldStatus !== 'APPROVED' && $status === 'APPROVED') {
            $this->inventoryService->processApprovedOrder($order);
            $this->dispatchOrderApprovedNotifications($order);
        } elseif ($oldStatus === 'APPROVED' && in_array($status, ['DECLINED', 'VOIDED', 'ERROR'])) {
            $this->inventoryService->restoreRejectedOrder($order);
        }

        return $order;
    }

    private function dispatchOrderApprovedNotifications(Order $order): void
    {
        SendWhatsAppJob::dispatch('notifyBusinessOrderApproved', [
            $order->reference,
            $order->customer_name,
            $order->customer_phone,
            $order->payment_method,
            $order->total_amount_cents,
        ]);

        SendWhatsAppJob::dispatch('notifyCustomerOrderApproved', [
            $order->customer_phone,
            $order->customer_name,
            $order->reference,
            $order->total_amount_cents,
        ]);
    }

    public function findByReference(string $reference): ?Order
    {
        return Order::where('reference', $reference)->first();
    }

    public function findByTransactionId(string $transactionId): ?Order
    {
        return Order::where('transaction_id', $transactionId)->first();
    }
}
