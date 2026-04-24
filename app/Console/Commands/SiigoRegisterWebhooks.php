<?php

namespace App\Console\Commands;

use App\Services\SiigoAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SiigoRegisterWebhooks extends Command
{
    protected $signature   = 'siigo:register-webhooks';
    protected $description = 'Suscribe los webhooks de productos en Siigo Nube (ejecutar una sola vez)';

    private array $topics = [
        'public.siigoapi.products.create',
        'public.siigoapi.products.update',
        'public.siigoapi.products.stock.update',
    ];

    public function handle(SiigoAuthService $auth): int
    {
        $webhookUrl = config('services.siigo.webhook_url');
        $partnerId  = config('services.siigo.partner_id');

        if (! $webhookUrl) {
            $this->error('SIIGO_WEBHOOK_URL no está configurado en .env');
            return self::FAILURE;
        }

        $this->info("Registrando webhooks en: {$webhookUrl}");

        foreach ($this->topics as $topic) {
            $response = Http::withHeaders($auth->headers())
                ->post(config('services.siigo.api_url') . '/v1/webhooks', [
                    'application_id' => $partnerId,
                    'topic'          => $topic,
                    'url'            => $webhookUrl,
                ]);

            if ($response->successful()) {
                $id = $response->json('id');
                $this->line("  <info>✓</info> {$topic} → ID: {$id}");
            } else {
                $this->line("  <error>✗</error> {$topic}: " . $response->body());
            }
        }

        $this->info('Listo. Guarda los IDs de suscripción si necesitas editarlos luego.');
        return self::SUCCESS;
    }
}
