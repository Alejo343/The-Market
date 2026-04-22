<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class ListOrders extends Command
{
    protected $signature = 'orders:list';
    protected $description = 'List all orders with their details';

    public function handle()
    {
        $orders = Order::all();

        if ($orders->isEmpty()) {
            $this->error('❌ No orders found in database');
            return 1;
        }

        $this->info("📦 Total orders: {$orders->count()}\n");

        foreach ($orders as $order) {
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("Order Reference: {$order->reference}");
            $this->info("Transaction ID: {$order->transaction_id}");
            $this->info("Status: {$order->status}");
            $this->info("Amount: {$order->total_amount_cents} COP");
            $this->info("Payment Method: {$order->payment_method}");
            $this->info("Customer Email: {$order->customer_email}");
            $this->info("Created: {$order->created_at}");
        }

        $this->info("\n💡 Use this command to test the webhook:");
        $this->info("php artisan wompi:test-webhook <transaction_id>\n");

        return 0;
    }
}
