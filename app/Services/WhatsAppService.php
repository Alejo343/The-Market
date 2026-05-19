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

    private ?string $extraNumber;

    public function __construct()
    {
        $this->url = rtrim(config('services.evolution.url', ''), '/');
        $this->key = config('services.evolution.key', '');
        $this->instance = config('services.evolution.instance', '');
        $this->businessNumber = config('services.evolution.business_number');
        $this->extraNumber = config('services.evolution.extra_number');
    }

    public function send(string $number, string $message): bool
    {
        $number = $this->sanitizeNumber($number);

        try {
            $response = Http::withHeaders([
                'apikey' => $this->key,
                'Content-Type' => 'application/json',
            ])->post("{$this->url}/message/sendText/{$this->instance}", [
                'number' => $number,
                'text' => $message,
            ]);

            if (! $response->successful()) {
                Log::error('WhatsApp send failed', [
                    'number' => $number,
                    'status' => $response->status(),
                    'body' => $response->body(),
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
        int $totalCents,
        array $items = []
    ): bool {
        $shortRef = substr($reference, -3);
        $total = number_format($totalCents / 100, 0, ',', '.');

        $message = "🛒 *Nueva venta online*\n"
            ."Pedido: *#{$shortRef}*\n"
            ."Cliente: {$customerName}\n"
            ."Teléfono: {$customerPhone}\n"
            ."Método: {$paymentMethod}\n";

        if (! empty($items)) {
            $message .= "\n*Productos:*\n";
            foreach ($items as $item) {
                $qty = $item['quantity'] ?? 1;
                $name = $item['name'] ?? 'Producto';
                $message .= "  • {$qty}x {$name}\n";
            }
        }

        $message .= "\nTotal: *\${$total}*";

        return $this->sendToBusinessNumbers($message);
    }

    public function notifyCustomerOrderApproved(
        string $customerPhone,
        string $customerName,
        string $reference,
        int $totalCents
    ): bool {
        $shortRef = substr($reference, -3);
        $total = number_format($totalCents / 100, 0, ',', '.');

        $message = "¡Hola {$customerName}! 👋\n"
            ."Tu pedido *#{$shortRef}* ha sido confirmado y está en proceso.\n"
            ."Total pagado: *\${$total}*\n"
            .'Te notificaremos cuando esté listo. ¡Gracias por tu compra! 🎉';

        return $this->send($customerPhone, $message);
    }

    public function notifyBusinessSaleCreated(
        int $saleId,
        string $cashierName,
        float $total,
        int $itemsCount
    ): bool {
        $totalFormatted = number_format($total, 0, ',', '.');

        $message = "💰 *Venta en tienda*\n"
            ."Venta #: {$saleId}\n"
            ."Cajero: {$cashierName}\n"
            ."Artículos: {$itemsCount}\n"
            ."Total: \${$totalFormatted}";

        return $this->sendToBusinessNumbers($message);
    }

    private function sendToBusinessNumbers(string $message): bool
    {
        if (! $this->businessNumber) {
            return false;
        }

        $result = $this->send($this->businessNumber, $message);

        if ($this->extraNumber) {
            $this->send($this->extraNumber, $message);
        }

        return $result;
    }

    private function sanitizeNumber(string $number): string
    {
        // Remove +, spaces, dashes, parentheses
        return preg_replace('/[^0-9]/', '', $number);
    }
}
