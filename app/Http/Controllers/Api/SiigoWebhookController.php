<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSiigoWebhook;
use App\Models\SiigoSyncLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SiigoWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        if (empty($payload)) {
            return response()->json(['error' => 'Empty payload'], 400);
        }

        // Verificar que el evento viene de la empresa configurada
        $companyKey = config('services.siigo.company_key');
        if ($companyKey && ($payload['company_key'] ?? null) !== $companyKey) {
            Log::warning('Siigo webhook: company_key inválido', ['received' => $payload['company_key'] ?? null]);
            SiigoSyncLog::record('webhook', 'error', 'company_key inválido en webhook', $payload['topic'] ?? null, null, null, $payload);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $topic = $payload['topic'] ?? null;
        $allowedTopics = [
            'public.siigoapi.products.create',
            'public.siigoapi.products.update',
            'public.siigoapi.products.stock.update',
        ];

        if (! in_array($topic, $allowedTopics)) {
            return response()->json(['received' => true]);
        }

        ProcessSiigoWebhook::dispatch($payload);

        return response()->json(['received' => true]);
    }
}
