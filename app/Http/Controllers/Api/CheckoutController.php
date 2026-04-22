<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\WompiService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(
        protected WompiService $wompiService,
        protected OrderService $orderService
    ) {}

    public function acceptance(): JsonResponse
    {
        try {
            $data = $this->wompiService->getAcceptance();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                $e->getCode() ?: 500
            );
        }
    }

    public function signature(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => 'required|string',
            'amountInCents' => 'required|integer',
        ]);

        try {
            $signature = $this->wompiService->generateSignature(
                $validated['reference'],
                $validated['amountInCents']
            );
            return response()->json(['signature' => $signature]);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                $e->getCode() ?: 500
            );
        }
    }

    public function pseInstitutions(): JsonResponse
    {
        try {
            $data = $this->wompiService->getPseInstitutions();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                $e->getCode() ?: 500
            );
        }
    }

    public function nequiPay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'amountInCents' => 'required|integer|min:1',
            'customerEmail' => 'required|email',
            'customerName' => 'required|string',
            'customerPhone' => 'required|string',
            'customerAddress' => 'required|string',
            'customerCity' => 'required|string',
            'items' => 'required|array',
            'notes' => 'nullable|string',
        ]);

        try {
            $reference = Str::ulid();

            $order = $this->orderService->createPendingOrder(
                $reference,
                'NEQUI',
                $validated['customerEmail'],
                $validated['customerName'],
                $validated['customerPhone'],
                $validated['customerAddress'],
                $validated['customerCity'],
                $validated['items'],
                $validated['amountInCents'],
                $validated['notes'] ?? ''
            );

            $txData = $this->wompiService->createNequiTransaction(
                $validated['phone'],
                $validated['amountInCents'],
                $validated['customerEmail'],
                $reference
            );

            $this->orderService->updateTransactionId($reference, $txData['transactionId']);

            return response()->json([
                'transactionId' => $txData['transactionId'],
                'reference' => $reference,
                'status' => $txData['status'],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                502
            );
        }
    }

    public function cardPay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cardToken' => 'required|string',
            'amountInCents' => 'required|integer|min:1',
            'customerEmail' => 'required|email',
            'customerName' => 'required|string',
            'customerPhone' => 'required|string',
            'customerAddress' => 'required|string',
            'customerCity' => 'required|string',
            'items' => 'required|array',
            'installments' => 'nullable|integer|min:1|max:36',
            'notes' => 'nullable|string',
        ]);

        try {
            $reference = Str::ulid();

            $order = $this->orderService->createPendingOrder(
                $reference,
                'CARD',
                $validated['customerEmail'],
                $validated['customerName'],
                $validated['customerPhone'],
                $validated['customerAddress'],
                $validated['customerCity'],
                $validated['items'],
                $validated['amountInCents'],
                "Installments: " . ($validated['installments'] ?? 1) . ($validated['notes'] ? "\n" . $validated['notes'] : '')
            );

            $txData = $this->wompiService->createCardTransaction(
                $validated['cardToken'],
                $validated['amountInCents'],
                $validated['customerEmail'],
                $validated['installments'] ?? 1,
                $reference
            );

            $this->orderService->updateTransactionId($reference, $txData['transactionId']);

            return response()->json([
                'transactionId' => $txData['transactionId'],
                'reference' => $reference,
                'status' => $txData['status'],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                502
            );
        }
    }

    public function psePay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amountInCents' => 'required|integer|min:1',
            'customerEmail' => 'required|email',
            'fullName' => 'required|string',
            'phone' => 'required|string',
            'customerAddress' => 'required|string',
            'customerCity' => 'required|string',
            'userType' => 'required|integer|in:0,1',
            'userLegalIdType' => 'required|string|in:CC,CE,NIT,PP',
            'userLegalId' => 'required|string',
            'financialInstitutionCode' => 'required|string',
            'redirectUrl' => 'required|url',
            'items' => 'required|array',
            'paymentDescription' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $reference = Str::ulid();

            $order = $this->orderService->createPendingOrder(
                $reference,
                'PSE',
                $validated['customerEmail'],
                $validated['fullName'],
                $validated['phone'],
                $validated['customerAddress'],
                $validated['customerCity'],
                $validated['items'],
                $validated['amountInCents'],
                "Bank: " . $validated['financialInstitutionCode'] . ($validated['notes'] ? "\n" . $validated['notes'] : '')
            );

            $txData = $this->wompiService->createPseTransaction(
                $validated['amountInCents'],
                $validated['customerEmail'],
                $validated['fullName'],
                $validated['phone'],
                $validated['userType'],
                $validated['userLegalIdType'],
                $validated['userLegalId'],
                $validated['financialInstitutionCode'],
                $validated['redirectUrl'],
                $validated['paymentDescription'] ?? '',
                $reference
            );

            // Guardar transaction_id antes del polling para que el webhook siempre encuentre la orden
            $this->orderService->updateTransactionId($reference, $txData['transactionId']);

            $asyncPaymentUrl = $this->wompiService->getAsyncPaymentUrl($txData['transactionId']);

            return response()->json([
                'transactionId' => $txData['transactionId'],
                'reference' => $reference,
                'asyncPaymentUrl' => $asyncPaymentUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                502
            );
        }
    }

    public function transactionStatus(string $transactionId): JsonResponse
    {
        try {
            $data = $this->wompiService->getTransactionStatus($transactionId);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                502
            );
        }
    }

    public function orderStatus(string $reference): JsonResponse
    {
        try {
            $order = $this->orderService->findByReference($reference);

            if (!$order) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }

            return response()->json([
                'id' => $order->id,
                'reference' => $order->reference,
                'status' => $order->status,
                'transactionId' => $order->transaction_id,
                'paymentMethod' => $order->payment_method,
                'totalAmountCents' => $order->total_amount_cents,
                'createdAt' => $order->created_at,
                'updatedAt' => $order->updated_at,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function orderHistory(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 10);
            $status = $request->query('status');

            $query = Order::orderBy('created_at', 'desc');

            if ($status) {
                $query->where('status', $status);
            }

            $orders = $query->paginate($perPage);

            return response()->json([
                'data' => $orders->items(),
                'pagination' => [
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
