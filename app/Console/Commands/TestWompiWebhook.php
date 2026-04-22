<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\WebhookController;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TestWompiWebhook extends Command
{
    protected $signature = 'wompi:test-webhook {transactionId}';
    protected $description = 'Test Wompi webhook locally';

    public function handle()
    {
        $transactionId = $this->argument('transactionId');

        $this->info("🔐 Testing webhook for transaction: {$transactionId}");

        // Verificar que la orden existe
        $order = Order::where('transaction_id', $transactionId)->first();

        if (!$order) {
            $this->error("❌ Orden NO ENCONTRADA con transaction_id: {$transactionId}");
            $this->info("💡 Crea una orden en el checkout primero.");
            return 1;
        }

        $this->info("✅ Orden encontrada: {$order->reference} (Status: {$order->status})");

        // Crear el webhook simulado
        $secret = config('services.wompi.events_secret');
        $timestamp = time();

        $data = [
            'event' => 'transaction.updated',
            'data' => [
                'transaction' => [
                    'id' => $transactionId,
                    'status' => 'APPROVED',
                    'amount_in_cents' => $order->total_amount_cents,
                    'reference' => $order->reference,
                ]
            ],
            'environment' => 'test',
            'signature' => [
                'properties' => ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'],
                'checksum' => null
            ],
            'timestamp' => $timestamp,
        ];

        // Calcular checksum según Wompi: propiedades (en orden) + timestamp + secreto
        $properties = ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'];
        $concatenated = '';
        $concatenated .= $transactionId;  // transaction.id
        $concatenated .= 'APPROVED';       // transaction.status
        $concatenated .= $order->total_amount_cents; // transaction.amount_in_cents
        $concatenated .= $timestamp;
        $concatenated .= $secret;

        $data['signature']['checksum'] = hash('sha256', $concatenated);

        $this->info("\n📊 Datos del webhook:");
        $this->info("  - Transaction ID: {$data['data']['transaction']['id']}");
        $this->info("  - Status: {$data['data']['transaction']['status']}");
        $this->info("  - Amount: {$data['data']['transaction']['amount_in_cents']} COP");
        $this->info("  - Timestamp: {$timestamp}");
        $this->info("  - Checksum: {$data['signature']['checksum']}\n");

        // Crear request
        $json = json_encode($data);
        $request = Request::create(
            '/api/webhooks/wompi/transaction',
            'POST',
            [],
            [],
            [],
            [],
            $json
        );
        $request->headers->set('Content-Type', 'application/json');

        // Ejecutar webhook
        $this->info("🚀 Enviando webhook...");
        $controller = app(WebhookController::class);
        $response = $controller->wompiTransaction($request);

        $this->info("📤 Response: " . $response->getContent() . "\n");

        // Verificar actualización
        $order->refresh();
        $this->info("✨ Orden después del webhook:");
        $this->info("  - Status: {$order->status}");
        $this->info("  - Notes: " . substr($order->notes ?? '', 0, 100) . "...\n");

        if ($order->status === 'APPROVED') {
            $this->info("✅ ¡ÉXITO! El webhook actualizó la orden a APPROVED");
            return 0;
        } else {
            $this->error("❌ Orden sigue en {$order->status}. Revisa los logs.");
            return 1;
        }
    }
}
