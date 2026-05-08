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
