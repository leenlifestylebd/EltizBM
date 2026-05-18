<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SteadfastService
{
    protected string $baseUrl = 'https://portal.packzy.com/api/v1';
    protected string $apiKey;
    protected string $secretKey;

    public function __construct(string $apiKey, string $secretKey)
    {
        $this->apiKey    = $apiKey;
        $this->secretKey = $secretKey;
    }

    // =============================================
    // HEADERS
    // =============================================
    private function headers(): array
    {
        return [
            'Api-Key'      => $this->apiKey,
            'Secret-Key'   => $this->secretKey,
            'Content-Type' => 'application/json',
        ];
    }

    // =============================================
    // PLACE SINGLE ORDER
    // =============================================
    public function placeOrder(array $data): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->post("{$this->baseUrl}/create_order", [
                    'invoice'           => $data['invoice'],
                    'recipient_name'    => $data['recipient_name'],
                    'recipient_phone'   => $data['recipient_phone'],
                    'recipient_address' => $data['recipient_address'],
                    'cod_amount'        => $data['cod_amount'] ?? 0,
                    'note'              => $data['note'] ?? null,
                    'item_description'  => $data['item_description'] ?? null,
                ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Steadfast placeOrder error', ['error' => $e->getMessage()]);
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    // =============================================
    // BULK ORDER CREATE
    // =============================================
    public function bulkCreate(array $orders): array
    {
        try {
            $data = [];
            foreach ($orders as $order) {
                $data[] = [
                    'invoice'           => $order['invoice'],
                    'recipient_name'    => $order['recipient_name'],
                    'recipient_phone'   => $order['recipient_phone'],
                    'recipient_address' => $order['recipient_address'],
                    'cod_amount'        => $order['cod_amount'] ?? 0,
                    'note'              => $order['note'] ?? null,
                ];
            }

            $response = Http::withHeaders($this->headers())
                ->post("{$this->baseUrl}/create_order/bulk-order", [
                    'data' => json_encode($data),
                ]);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Steadfast bulkCreate error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // =============================================
    // CHECK STATUS
    // =============================================
    public function statusByConsignmentId(string $id): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/status_by_cid/{$id}");
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public function statusByInvoice(string $invoice): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/status_by_invoice/{$invoice}");
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public function statusByTrackingCode(string $trackingCode): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/status_by_trackingcode/{$trackingCode}");
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    // =============================================
    // GET BALANCE
    // =============================================
    public function getBalance(): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/get_balance");
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 500, 'current_balance' => 0, 'message' => $e->getMessage()];
        }
    }

    // =============================================
    // CREATE RETURN REQUEST
    // =============================================
    public function createReturnRequest(string $identifier, string $reason = ''): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->post("{$this->baseUrl}/create_return_request", [
                    'consignment_id' => $identifier,
                    'reason'         => $reason,
                ]);
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    // =============================================
    // FRAUD / RECIPIENT CHECK
    // =============================================
    public function fraudCheck(string $phone): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/fraud_check/{$phone}");

            // Rate limit hit
            if ($response->status() === 429) {
                $body = $response->json();
                return [
                    'found'       => false,
                    'rate_limited'=> true,
                    'limit'       => $body['limit'] ?? null,
                    'error'       => 'Daily fraud check limit reached (' . ($body['limit'] ?? '?') . ' searches/day)',
                ];
            }

            if (!$response->successful()) {
                return ['found' => false, 'error' => 'API error: ' . $response->status()];
            }

            $data = $response->json();

            // Normalise — Steadfast may wrap in 'data' key or return flat
            if (isset($data['data']) && is_array($data['data'])) {
                $d = $data['data'];
            } else {
                $d = $data;
            }

            $total     = (int) ($d['total']           ?? $d['total_orders']  ?? 0);
            $delivered = (int) ($d['delivered']        ?? $d['success']       ?? 0);
            $returned  = (int) ($d['returned']         ?? $d['return']        ?? 0);
            $cancelled = (int) ($d['cancelled']        ?? $d['cancel']        ?? 0);
            $inReview  = (int) ($d['in_review']        ?? 0);

            // Use API-provided rate if available, otherwise calculate
            $successRate = null;
            if (isset($d['success_rate'])) {
                $successRate = round((float) $d['success_rate'], 1);
            } elseif (isset($d['delivery_percentage'])) {
                $successRate = round((float) $d['delivery_percentage'], 1);
            } elseif ($total > 0) {
                $successRate = round(($delivered / $total) * 100, 1);
            }

            if ($total === 0) {
                $risk = 'new';
            } elseif ($successRate >= 80) {
                $risk = 'low';
            } elseif ($successRate >= 50) {
                $risk = 'medium';
            } else {
                $risk = 'high';
            }

            return [
                'found'        => true,
                'phone'        => $phone,
                'total'        => $total,
                'delivered'    => $delivered,
                'returned'     => $returned,
                'cancelled'    => $cancelled,
                'in_review'    => $inReview,
                'success_rate' => $successRate,
                'risk'         => $risk,
            ];

        } catch (\Exception $e) {
            Log::error('Steadfast fraudCheck error', ['phone' => $phone, 'error' => $e->getMessage()]);
            return ['found' => false, 'error' => $e->getMessage()];
        }
    }

    // =============================================
    // GET POLICE STATIONS
    // =============================================
    public function getPoliceStations(): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/police_stations");
            return $response->json() ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    // =============================================
    // STEADFAST STATUS → OUR STATUS MAP
    // =============================================
    public static function mapStatus(string $steadfastStatus): string
    {
        return match($steadfastStatus) {
            'delivered', 'partial_delivered'                                         => 'delivered',
            'cancelled', 'cancelled_approval_pending'                               => 'cancelled',
            'delivered_approval_pending', 'partial_delivered_approval_pending'      => 'in_transit',
            'hold'                                                                   => 'picked',
            'in_review'                                                              => 'pending',
            default                                                                  => 'pending',
        };
    }
}
