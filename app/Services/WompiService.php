<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WompiService
{
    private string $wompiApi;
    private string $publicKey;
    private string $privateKey;
    private string $integritySecret;

    public function __construct()
    {
        $this->wompiApi = config('services.wompi.api_url');
        $this->publicKey = config('services.wompi.public_key');
        $this->privateKey = config('services.wompi.private_key');
        $this->integritySecret = config('services.wompi.integrity_secret');
    }

    public function getAcceptance(): array
    {
        if (!$this->publicKey) {
            throw new \Exception('Configuración de pagos incompleta');
        }

        $response = Http::get("{$this->wompiApi}/merchants/{$this->publicKey}");

        if (!$response->successful()) {
            throw new \Exception('No se pudieron obtener los términos');
        }

        $data = $response->json('data');

        return [
            'endUserPolicy' => $data['presigned_acceptance']['permalink'],
            'personalDataAuth' => $data['presigned_personal_data_auth']['permalink'],
        ];
    }

    public function generateSignature(string $reference, int $amountInCents): string
    {
        if (!$this->integritySecret) {
            throw new \Exception('Configuración incompleta');
        }

        $raw = "{$reference}{$amountInCents}COP{$this->integritySecret}";
        return hash('sha256', $raw);
    }

    public function getPseInstitutions(): array
    {
        if (!$this->publicKey) {
            throw new \Exception('Configuración de pagos incompleta');
        }

        $response = Http::withToken($this->publicKey)
            ->get("{$this->wompiApi}/pse/financial_institutions");

        if (!$response->successful()) {
            throw new \Exception('No se pudo obtener la lista de bancos');
        }

        $data = $response->json('data', []);

        $institutions = array_map(fn($institution) => [
            'code' => $institution['financial_institution_code'],
            'name' => $institution['financial_institution_name'],
        ], $data);

        return ['institutions' => $institutions];
    }

    private function getAcceptanceTokens(): array
    {
        if (!$this->publicKey) {
            throw new \Exception('Configuración de pagos incompleta');
        }

        $response = Http::get("{$this->wompiApi}/merchants/{$this->publicKey}");

        if (!$response->successful()) {
            throw new \Exception('No se pudieron obtener los tokens de aceptación');
        }

        $data = $response->json('data');

        return [
            'acceptance_token' => $data['presigned_acceptance']['acceptance_token'],
            'accept_personal_auth' => $data['presigned_personal_data_auth']['acceptance_token'],
        ];
    }

    private function generateReference(string $prefix): string
    {
        $rand = strtoupper(substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 5));
        return "{$prefix}-" . time() . "-{$rand}";
    }

    public function createNequiTransaction(string $phone, int $amountInCents, string $customerEmail, ?string $reference = null): array
    {
        if (!$this->privateKey) {
            throw new \Exception('Private key incompleta');
        }
        if (!$this->publicKey) {
            throw new \Exception('Public key incompleta');
        }
        if (!$this->integritySecret) {
            throw new \Exception('Integrity secret incompleta');
        }

        $reference ??= $this->generateReference('BARRIL-NQ');
        $signature = $this->generateSignature($reference, $amountInCents);
        $tokens = $this->getAcceptanceTokens();
        $acceptance_token = $tokens['acceptance_token'];
        $accept_personal_auth = $tokens['accept_personal_auth'];

        $url = "{$this->wompiApi}/transactions";
        $payload = [
            'acceptance_token' => $acceptance_token,
            'accept_personal_auth' => $accept_personal_auth,
            'amount_in_cents' => $amountInCents,
            'currency' => 'COP',
            'signature' => $signature,
            'customer_email' => $customerEmail,
            'reference' => $reference,
            'payment_method' => [
                'type' => 'NEQUI',
                'phone_number' => $phone,
            ],
        ];

        $response = Http::withToken($this->privateKey)->post($url, $payload);

        if (!$response->successful()) {
            $status = $response->status();
            $body = $response->body();
            throw new \Exception("Wompi {$status}: {$body}");
        }

        $txData = $response->json('data');
        return [
            'transactionId' => $txData['id'],
            'reference' => $reference,
            'status' => $txData['status'],
        ];
    }

    public function createCardTransaction(string $cardToken, int $amountInCents, string $customerEmail, int $installments = 1, ?string $reference = null): array
    {
        if (!$this->privateKey) {
            throw new \Exception('Private key incompleta');
        }
        if (!$this->publicKey) {
            throw new \Exception('Public key incompleta');
        }
        if (!$this->integritySecret) {
            throw new \Exception('Integrity secret incompleta');
        }

        $reference ??= $this->generateReference('BARRIL-CD');
        $signature = $this->generateSignature($reference, $amountInCents);
        $tokens = $this->getAcceptanceTokens();
        $acceptance_token = $tokens['acceptance_token'];
        $accept_personal_auth = $tokens['accept_personal_auth'];

        $url = "{$this->wompiApi}/transactions";
        $payload = [
            'acceptance_token' => $acceptance_token,
            'accept_personal_auth' => $accept_personal_auth,
            'amount_in_cents' => $amountInCents,
            'currency' => 'COP',
            'signature' => $signature,
            'customer_email' => $customerEmail,
            'reference' => $reference,
            'payment_method' => [
                'type' => 'CARD',
                'token' => $cardToken,
                'installments' => $installments,
            ],
        ];

        $response = Http::withToken($this->privateKey)->post($url, $payload);

        if (!$response->successful()) {
            $status = $response->status();
            $body = $response->body();
            throw new \Exception("Wompi {$status}: {$body}");
        }

        $txData = $response->json('data');
        return [
            'transactionId' => $txData['id'],
            'reference' => $reference,
            'status' => $txData['status'],
        ];
    }

    public function createPseTransaction(
        int $amountInCents,
        string $customerEmail,
        string $fullName,
        string $phone,
        int $userType,
        string $userLegalIdType,
        string $userLegalId,
        string $financialInstitutionCode,
        string $redirectUrl,
        string $paymentDescription = '',
        ?string $reference = null
    ): array {
        if (!$this->privateKey || !$this->publicKey || !$this->integritySecret) {
            throw new \Exception('Configuración de pagos incompleta');
        }

        $reference ??= $this->generateReference('BARRIL-PSE');
        $signature = $this->generateSignature($reference, $amountInCents);

        $tokens = $this->getAcceptanceTokens();
        $acceptance_token = $tokens['acceptance_token'];
        $accept_personal_auth = $tokens['accept_personal_auth'];

        $description = substr($paymentDescription ?: "Pago a Barril Market, ref: {$reference}", 0, 64);

        $url = "{$this->wompiApi}/transactions";
        $payload = [
            'acceptance_token' => $acceptance_token,
            'accept_personal_auth' => $accept_personal_auth,
            'amount_in_cents' => $amountInCents,
            'currency' => 'COP',
            'signature' => $signature,
            'customer_email' => $customerEmail,
            'reference' => $reference,
            'redirect_url' => $redirectUrl,
            'payment_method' => [
                'type' => 'PSE',
                'user_type' => $userType,
                'user_legal_id_type' => $userLegalIdType,
                'user_legal_id' => $userLegalId,
                'financial_institution_code' => $financialInstitutionCode,
                'payment_description' => $description,
            ],
            'customer_data' => [
                'phone_number' => $phone,
                'full_name' => $fullName,
            ],
        ];

        $response = Http::withToken($this->privateKey)->post($url, $payload);

        if (!$response->successful()) {
            $status = $response->status();
            $body = $response->body();
            throw new \Exception("Wompi {$status}: {$body}");
        }

        $txData = $response->json('data');
        $transactionId = $txData['id'];
        $asyncUrl = $this->pollAsyncPaymentUrl($transactionId);

        return [
            'transactionId' => $transactionId,
            'reference' => $reference,
            'asyncPaymentUrl' => $asyncUrl,
        ];
    }

    public function getTransactionStatus(string $transactionId): array
    {
        if (!$this->publicKey) {
            throw new \Exception('Configuración de pagos incompleta');
        }

        $response = Http::withToken($this->publicKey)
            ->get("{$this->wompiApi}/transactions/{$transactionId}");

        if (!$response->successful()) {
            throw new \Exception('No se pudo verificar la transacción');
        }

        $data = $response->json('data');

        return [
            'status' => $data['status'],
            'statusMessage' => $data['status_message'] ?? '',
        ];
    }

    private function pollAsyncPaymentUrl(string $transactionId, int $maxWaitMs = 25000): string
    {
        $startTime = microtime(true) * 1000;

        while ((microtime(true) * 1000) - $startTime < $maxWaitMs) {
            $response = Http::withToken($this->privateKey)
                ->get("{$this->wompiApi}/transactions/{$transactionId}");

            if ($response->successful()) {
                $data = $response->json('data');
                $url = $data['payment_method']['extra']['async_payment_url'] ?? null;

                if ($url) {
                    return $url;
                }

                if (in_array($data['status'], ['DECLINED', 'ERROR', 'VOIDED'])) {
                    throw new \Exception($data['status_message'] ?? 'Transacción rechazada');
                }
            }

            usleep(1500000); // 1.5 segundos
        }

        throw new \Exception('Tiempo agotado esperando la URL de pago PSE');
    }
}
