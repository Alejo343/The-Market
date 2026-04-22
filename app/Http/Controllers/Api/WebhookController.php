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
            $data = $request->json()->all();

            if (!isset($data['event'], $data['data'], $data['signature'], $data['timestamp'])) {
                return response()->json(['error' => 'Invalid webhook format'], 400);
            }

            if (!$this->verifyWebhookSignature($data)) {
                \Log::warning('Webhook: Invalid signature', ['event' => $data['event'] ?? null]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $event = $data['event'];
            $txData = $data['data'];

            if ($event !== 'transaction.updated') {
                return response()->json(['received' => true]);
            }

            $transaction = $txData['transaction'] ?? null;
            if (!$transaction) {
                return response()->json(['error' => 'Missing transaction data'], 400);
            }

            $transactionId = $transaction['id'] ?? null;
            $status = $transaction['status'] ?? null;

            if (!$transactionId || !$status) {
                return response()->json(['error' => 'Missing transaction ID or status'], 400);
            }

            $order = $this->orderService->findByTransactionId($transactionId);

            if (!$order) {
                \Log::warning("Webhook: Order not found for transaction {$transactionId}");
                return response()->json(['received' => true]);
            }

            $statusMessage = $transaction['status_message'] ?? '';
            $this->orderService->updateStatus($transactionId, $status, $statusMessage);

            \Log::info("Order {$order->reference} updated to status {$status}");

            return response()->json(['received' => true, 'order_id' => $order->id]);
        } catch (\Exception $e) {
            \Log::error('Webhook error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    private function verifyWebhookSignature(array $data): bool
    {
        $secret = config('services.wompi.events_secret');
        if (!$secret) {
            \Log::error('WOMPI_EVENTS_SECRET not configured');
            return false;
        }

        $signature = $data['signature'] ?? [];
        $receivedChecksum = $signature['checksum'] ?? null;
        $properties = $signature['properties'] ?? [];
        $timestamp = $data['timestamp'] ?? null;

        if (!$receivedChecksum || !$properties || !$timestamp) {
            \Log::warning('Webhook: Missing signature components', [
                'has_checksum' => !empty($receivedChecksum),
                'has_properties' => !empty($properties),
                'has_timestamp' => !empty($timestamp),
            ]);
            return false;
        }

        $concatenated = '';
        foreach ($properties as $property) {
            $value = $this->getNestedValue($data, $property);
            $concatenated .= $value ?? '';
        }
        $concatenated .= $timestamp . $secret;

        $calculatedChecksum = hash('sha256', $concatenated);

        $match = hash_equals($receivedChecksum, $calculatedChecksum);

        if (!$match) {
            \Log::warning('Webhook signature mismatch', [
                'received' => $receivedChecksum,
                'calculated' => $calculatedChecksum,
            ]);
        }

        return $match;
    }

    private function getNestedValue(array $data, string $path)
    {
        $keys = explode('.', $path);
        // Wompi properties point to fields within data.transaction
        $value = $data['data'] ?? $data;

        foreach ($keys as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }
}
