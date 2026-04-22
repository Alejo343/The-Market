<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $url;
    private string $key;
    private string $instance;
    private ?string $businessNumber;

    public function __construct()
    {
        $this->url      = rtrim(config('services.evolution.url', ''), '/');
        $this->key      = config('services.evolution.key', '');
        $this->instance = config('services.evolution.instance', '');
        $this->businessNumber = config('services.evolution.business_number');
    }

    public function send(string $number, string $message): bool
    {
        $number = $this->sanitizeNumber($number);

        try {
            $response = Http::withHeaders([
                'apikey'       => $this->key,
                'Content-Type' => 'application/json',
            ])->post("{$this->url}/message/sendText/{$this->instance}", [
                'number' => $number,
                'text'   => $message,
            ]);

            if (!$response->successful()) {
                Log::error('WhatsApp send failed', [
                    'number' => $number,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsApp exception', ['message' => $e->getMessage(), 'number' => $number]);
            return false;
        }
    }

    public function notifyBusinessOrderApproved(
        string $reference,
        string $customerName,
        string $customerPhone,
        string $paymentMethod,
        int $totalCents
    ): bool {
        if (!$this->businessNumber) {
            return false;
        }

        $total = number_format($totalCents / 100, 0, ',', '.');

        $message = "🛒 *Nueva venta online*\n"
            . "Pedido: #{$reference}\n"
            . "Cliente: {$customerName}\n"
            . "Teléfono: {$customerPhone}\n"
            . "Total: \${$total}\n"
            . "Método: {$paymentMethod}";

        return $this->send($this->businessNumber, $message);
    }

    public function notifyCustomerOrderApproved(
        string $customerPhone,
        string $customerName,
        string $reference,
        int $totalCents
    ): bool {
        $total = number_format($totalCents / 100, 0, ',', '.');

        $message = "¡Hola {$customerName}! 👋\n"
            . "Tu pedido *#{$reference}* ha sido confirmado y está en proceso.\n"
            . "Total pagado: *\${$total}*\n"
            . "Te notificaremos cuando esté listo. ¡Gracias por tu compra! 🎉";

        return $this->send($customerPhone, $message);
    }

    public function notifyBusinessSaleCreated(
        int $saleId,
        string $cashierName,
        float $total,
        int $itemsCount
    ): bool {
        if (!$this->businessNumber) {
            return false;
        }

        $totalFormatted = number_format($total, 0, ',', '.');

        $message = "💰 *Venta en tienda*\n"
            . "Venta #: {$saleId}\n"
            . "Cajero: {$cashierName}\n"
            . "Artículos: {$itemsCount}\n"
            . "Total: \${$totalFormatted}";

        return $this->send($this->businessNumber, $message);
    }

    private function sanitizeNumber(string $number): string
    {
        // Remove +, spaces, dashes, parentheses
        return preg_replace('/[^0-9]/', '', $number);
    }
}
