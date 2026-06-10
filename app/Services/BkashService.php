<?php

namespace App\Services;

use App\Models\PaymentGatewaySetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BkashService
{
    private string $baseUrl;
    private array  $creds;
    private int    $bizId;
    private bool   $sandbox;

    public function __construct(PaymentGatewaySetting $setting)
    {
        $this->creds   = $setting->credentials;
        $this->bizId   = $setting->business_id;
        $this->sandbox = $setting->sandbox;

        $this->baseUrl = $this->sandbox
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/checkout';
    }

    // ── Get/refresh auth token ─────────────────────────────────
    public function getToken(): string
    {
        $cacheKey = "bkash_token_{$this->bizId}";

        return Cache::remember($cacheKey, 3500, function () {
            $res = Http::withHeaders([
                'username'  => $this->creds['username'],
                'password'  => $this->creds['password'],
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/token/grant", [
                'app_key'    => $this->creds['app_key'],
                'app_secret' => $this->creds['app_secret'],
            ]);

            if (!$res->successful() || empty($res['id_token'])) {
                throw new \Exception('bKash token grant failed: ' . $res->body());
            }

            return $res['id_token'];
        });
    }

    // ── Create payment ─────────────────────────────────────────
    public function createPayment(string $orderId, float $amount, string $callbackUrl): array
    {
        $token = $this->getToken();

        $res = Http::withHeaders([
            'Authorization' => $token,
            'X-APP-Key'     => $this->creds['app_key'],
            'Content-Type'  => 'application/json',
        ])->post("{$this->baseUrl}/create", [
            'mode'                => '0011',
            'payerReference'      => $orderId,
            'callbackURL'         => $callbackUrl,
            'amount'              => number_format($amount, 2, '.', ''),
            'currency'            => 'BDT',
            'intent'              => 'sale',
            'merchantInvoiceNumber' => $orderId,
        ]);

        if (!$res->successful()) {
            throw new \Exception('bKash create payment failed: ' . $res->body());
        }

        return $res->json();
    }

    // ── Execute payment after callback ─────────────────────────
    public function executePayment(string $paymentId): array
    {
        $token = $this->getToken();

        $res = Http::withHeaders([
            'Authorization' => $token,
            'X-APP-Key'     => $this->creds['app_key'],
            'Content-Type'  => 'application/json',
        ])->post("{$this->baseUrl}/execute", [
            'paymentID' => $paymentId,
        ]);

        if (!$res->successful()) {
            throw new \Exception('bKash execute failed: ' . $res->body());
        }

        return $res->json();
    }

    // ── Query payment status ───────────────────────────────────
    public function queryPayment(string $paymentId): array
    {
        $token = $this->getToken();

        $res = Http::withHeaders([
            'Authorization' => $token,
            'X-APP-Key'     => $this->creds['app_key'],
            'Content-Type'  => 'application/json',
        ])->post("{$this->baseUrl}/payment/status", [
            'paymentID' => $paymentId,
        ]);

        return $res->json() ?? [];
    }

    // ── Refund ─────────────────────────────────────────────────
    public function refund(string $paymentId, float $amount, string $trxId, string $reason): array
    {
        $token = $this->getToken();

        $res = Http::withHeaders([
            'Authorization' => $token,
            'X-APP-Key'     => $this->creds['app_key'],
            'Content-Type'  => 'application/json',
        ])->post("{$this->baseUrl}/payment/refund", [
            'paymentID'     => $paymentId,
            'amount'        => number_format($amount, 2, '.', ''),
            'trxID'         => $trxId,
            'sku'           => 'refund',
            'reason'        => $reason,
        ]);

        return $res->json() ?? [];
    }
}
