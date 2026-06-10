<?php

namespace App\Services;

use App\Models\PaymentGatewaySetting;
use Illuminate\Support\Facades\Http;

class NagadService
{
    private string $baseUrl;
    private array  $creds;
    private bool   $sandbox;

    public function __construct(PaymentGatewaySetting $setting)
    {
        $this->creds   = $setting->credentials;
        $this->sandbox = $setting->sandbox;

        $this->baseUrl = $this->sandbox
            ? 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs'
            : 'https://api.mynagad.com/api/dfs';
    }

    // ── Encrypt sensitive data with Nagad public key ───────────
    private function encryptData(string $data): string
    {
        $publicKey = "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split($this->creds['public_key'], 64, "\n")
            . "-----END PUBLIC KEY-----";

        openssl_public_encrypt($data, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        return base64_encode($encrypted);
    }

    // ── Sign data with merchant private key ────────────────────
    private function signData(string $data): string
    {
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n"
            . chunk_split($this->creds['private_key'], 64, "\n")
            . "-----END RSA PRIVATE KEY-----";

        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    // ── Initialize payment ─────────────────────────────────────
    public function initializePayment(string $orderId, float $amount, string $callbackUrl): array
    {
        $merchantId = $this->creds['merchant_id'];
        $datetime   = now()->format('YmdHis');

        $sensitiveData = json_encode([
            'merchantId'        => $merchantId,
            'datetime'          => $datetime,
            'orderId'           => $orderId,
            'challenge'         => $this->generateChallenge(),
        ]);

        $checkoutData = json_encode([
            'merchantId'        => $merchantId,
            'datetime'          => $datetime,
            'orderId'           => $orderId,
            'challenge'         => $this->generateChallenge(),
            'amount'            => number_format($amount, 2, '.', ''),
            'currencyCode'      => '050',
            'productionURL'     => $callbackUrl,
        ]);

        $res = Http::withHeaders([
            'X-KM-Api-Version'  => 'v-0.2.0',
            'X-KM-IP-V4'        => request()->ip(),
            'X-KM-Client-Type'  => 'PC_WEB',
        ])->post("{$this->baseUrl}/check-out/initialize/{$merchantId}/{$orderId}", [
            'dateTime'          => $datetime,
            'sensitiveData'     => $this->encryptData($sensitiveData),
            'signature'         => $this->signData($checkoutData),
            'merchantCallbackURL' => $callbackUrl,
            'amount'            => number_format($amount, 2, '.', ''),
            'currencyCode'      => '050',
        ]);

        if (!$res->successful()) {
            throw new \Exception('Nagad initialize failed: ' . $res->body());
        }

        return $res->json();
    }

    // ── Complete / verify payment ──────────────────────────────
    public function verifyPayment(string $paymentRefId): array
    {
        $merchantId = $this->creds['merchant_id'];

        $res = Http::withHeaders([
            'X-KM-Api-Version'  => 'v-0.2.0',
            'X-KM-IP-V4'        => request()->ip(),
            'X-KM-Client-Type'  => 'PC_WEB',
        ])->post("{$this->baseUrl}/verify/payment/{$paymentRefId}");

        return $res->json() ?? [];
    }

    private function generateChallenge(): string
    {
        return bin2hex(random_bytes(16));
    }
}
