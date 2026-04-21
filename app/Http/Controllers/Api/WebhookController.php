<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Services\WompiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WebhookController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected WompiService $wompiService
    ) {}

    public function wompiTransaction(Request $request): JsonResponse
    {
        try {
            $signature = $request->header('X-Wompi-Signature');
            if (!$this->verifyWebhookSignature($request, $signature)) {
                \Log::warning('Webhook: Invalid signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $data = $request->json()->all();

            if (!isset($data['event'], $data['data'])) {
                return response()->json(['error' => 'Invalid webhook format'], 400);
            }

            $event = $data['event'];
            $txData = $data['data'];

            if ($event !== 'transaction.updated') {
                return response()->json(['received' => true]);
            }

            $transactionId = $txData['id'] ?? null;
            $status = $txData['status'] ?? null;
            $statusMessage = $txData['status_message'] ?? '';

            if (!$transactionId || !$status) {
                return response()->json(['error' => 'Missing transaction ID or status'], 400);
            }

            $order = $this->orderService->findByTransactionId($transactionId);

            if (!$order) {
                \Log::warning("Webhook: Order not found for transaction {$transactionId}");
                return response()->json(['received' => true]);
            }

            $this->orderService->updateStatus($transactionId, $status, $statusMessage);

            \Log::info("Order {$order->reference} updated to status {$status}");

            return response()->json(['received' => true, 'order_id' => $order->id]);
        } catch (\Exception $e) {
            \Log::error('Webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    private function verifyWebhookSignature(Request $request, ?string $signature): bool
    {
        if (!$signature) {
            \Log::warning('No signature header provided');
            return false;
        }

        $signature = trim($signature);

        $secret = config('services.wompi.events_secret');
        if (!$secret) {
            \Log::error('WOMPI_EVENTS_SECRET not configured');
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        \Log::info('Webhook signature debug', [
            'payload' => $payload,
            'payload_length' => strlen($payload),
            'secret_length' => strlen($secret),
            'received_signature' => $signature,
            'received_signature_length' => strlen($signature),
            'received_signature_hex' => bin2hex($signature),
            'expected_signature' => $expectedSignature,
            'expected_signature_length' => strlen($expectedSignature),
            'char_by_char_match' => implode('|', str_split($signature)) === implode('|', str_split($expectedSignature)),
        ]);

        $match = hash_equals($signature, $expectedSignature);

        \Log::info('Hash equals result', [
            'match' => $match,
            'received_bytes' => array_values(unpack('C*', $signature)),
            'expected_bytes' => array_values(unpack('C*', $expectedSignature)),
        ]);

        return $match;
    }
}
