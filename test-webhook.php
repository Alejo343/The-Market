<?php
require 'vendor/autoload.php';

use App\Http\Controllers\Api\WebhookController;
use Illuminate\Http\Request;

// Inicializar Laravel
$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// ID de la orden que mencionaste
$transactionId = '01KPSGM4T0RY1VK2B4618Q71N1';

// Obtener el secreto
$secret = env('WOMPI_EVENTS_SECRET');
echo "🔐 Secret: " . substr($secret, 0, 10) . "...\n";

// Primero: verificar si la orden existe
$order = \App\Models\Order::where('transaction_id', $transactionId)->first();

if (!$order) {
    echo "⚠️  Orden NO ENCONTRADA con transaction_id: {$transactionId}\n";
    echo "💡 Necesitas que la orden exista primero. Crea una orden en el checkout.\n";
    exit(1);
}

echo "✅ Orden encontrada: {$order->reference} (Status: {$order->status})\n\n";

// Crear el webhook simulado
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
        'checksum' => null // Se calcula abajo
    ],
    'timestamp' => $timestamp,
];

// Calcular checksum según Wompi: propiedades + timestamp + secreto
$concatenated = $transactionId . 'APPROVED' . $order->total_amount_cents . $timestamp . $secret;
$data['signature']['checksum'] = hash('sha256', $concatenated);

echo "📊 Datos del webhook:\n";
echo "  - Transaction ID: {$data['data']['transaction']['id']}\n";
echo "  - Status: {$data['data']['transaction']['status']}\n";
echo "  - Amount: {$data['data']['transaction']['amount_in_cents']} COP\n";
echo "  - Timestamp: {$timestamp}\n";
echo "  - Checksum: {$data['signature']['checksum']}\n\n";

// Crear request simulado
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

// Ejecutar el webhook
echo "🚀 Enviando webhook...\n";
$controller = app(WebhookController::class);
$response = $controller->wompiTransaction($request);

echo "📤 Response: " . $response->getContent() . "\n\n";

// Verificar que la orden se actualizó
$order->refresh();
echo "✨ Orden después del webhook:\n";
echo "  - Status: {$order->status}\n";
echo "  - Notes: " . substr($order->notes, 0, 100) . "...\n";

if ($order->status === 'APPROVED') {
    echo "\n✅ ¡ÉXITO! El webhook actualizó la orden a APPROVED\n";
} else {
    echo "\n❌ Orden sigue en {$order->status}. Revisa los logs.\n";
}
