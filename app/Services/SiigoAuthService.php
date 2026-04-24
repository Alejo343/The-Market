<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SiigoAuthService
{
    private const CACHE_KEY = 'siigo_access_token';
    private const TOKEN_TTL = 60 * 23; // 23 horas (token dura 24h)

    public function getToken(): string
    {
        return Cache::remember(self::CACHE_KEY, self::TOKEN_TTL * 60, function () {
            return $this->fetchToken();
        });
    }

    public function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Partner-Id'    => config('services.siigo.partner_id'),
            'Content-Type'  => 'application/json',
        ];
    }

    public function forgetToken(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function fetchToken(): string
    {
        $response = Http::withHeaders(['Partner-Id' => config('services.siigo.partner_id')])
            ->post(config('services.siigo.api_url') . '/auth', [
                'username'   => config('services.siigo.username'),
                'access_key' => config('services.siigo.access_key'),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Siigo auth failed: ' . $response->body());
        }

        $token = $response->json('access_token');

        if (! $token) {
            throw new RuntimeException('Siigo auth: no access_token en respuesta');
        }

        return $token;
    }
}
