<?php

namespace App\Services;

use App\Models\User;
use App\Models\WoocommerceSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WooCommerceService
{
    protected WoocommerceSetting $settings;
    protected string $baseUrl;
    protected int $systemUserId;

    public function __construct(WoocommerceSetting $settings)
    {
        $this->settings = $settings;
        $this->baseUrl  = rtrim($settings->store_url, '/') . '/wp-json/wc/v3/';

        // Resolve the business owner's user ID for system-created records.
        // Falls back to the authenticated user, then to the first owner of the business.
        $this->systemUserId = auth()->id()
            ?? User::where('business_id', $settings->business_id)
                   ->where('role', 'owner')
                   ->value('id')
            ?? User::where('business_id', $settings->business_id)->value('id')
            ?? 1;
    }

    // =============================================
    // HTTP CLIENT
    // =============================================
    protected function client()
    {
        return Http::withBasicAuth(
            $this->settings->consumer_key,
            $this->settings->consumer_secret
        )->timeout(30)->acceptJson();
    }

    // =============================================
    // TEST CONNECTION
    // =============================================
    public function testConnection(): array
    {
        try {
            $response = $this->client()->get($this->baseUrl . 'system_status');

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success'  => true,
                    'message'  => 'Connected successfully!',
                    'store'    => $data['environment']['site_url'] ?? $this->settings->store_url,
                    'wc_version' => $data['environment']['wc_version'] ?? 'Unknown',
                ];
            }

            return [
                'success' => false,
                'message' => 'Connection failed: HTTP ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    // =============================================
    // SYNC PRODUCTS: WooCommerce → Fulfillo
    // =============================================
    public function syncProductsFromWoo(int $businessId): array
    {
        $imported = 0;
        $updated  = 0;
        $errors   = [];
        $page     = 1;

        do {
            try {
                $response = $this->client()->get($this->baseUrl . 'products', [
                    'per_page' => 50,
                    'page'     => $page,
                    'status'   => 'publish',
                ]);

                if (!$response->successful()) {
                    $errors[] = 'Failed to fetch page ' . $page;
                    break;
                }

                $wooProducts = $response->json();

                if (empty($wooProducts)) break;

                foreach ($wooProducts as $wp) {
                    try {
                        $existing = Product::where('business_id', $businessId)
                            ->where('woo_product_id', $wp['id'])
                            ->first();

                        // Base data that always updates
                        $productData = [
                            'business_id'    => $businessId,
                            'name'           => $wp['name'],
                            'slug'           => $wp['slug'] . '-' . $businessId,
                            'description'    => strip_tags($wp['short_description'] ?? $wp['description'] ?? ''),
                            'price'          => floatval($wp['regular_price'] ?: $wp['price'] ?: 0),
                            'sale_price'     => $wp['sale_price'] ? floatval($wp['sale_price']) : null,
                            'stock'          => $wp['stock_quantity'] ?? 0,
                            'track_stock'    => $wp['manage_stock'] ?? true,
                            'is_active'      => $wp['status'] === 'publish',
                            'type'           => $wp['type'] === 'variable' ? 'variable' : 'simple',
                            'woo_product_id' => $wp['id'],
                            'woo_synced_at'  => now(),
                        ];

                        if ($existing) {
                            // Don't overwrite SKU if user has set a custom one
                            // (only auto-generated WOO-xxx SKUs get refreshed)
                            $currentSku = $existing->sku ?? '';
                            if (empty($currentSku) || str_starts_with($currentSku, 'WOO-')) {
                                $productData['sku'] = $wp['sku'] ?: 'WOO-' . $wp['id'];
                            }

                            // Don't overwrite images if user has locally-uploaded ones
                            // (local uploads are storage paths, not http URLs)
                            $hasLocalImages = collect($existing->images ?? [])
                                ->contains(fn($img) => !str_starts_with($img, 'http'));
                            if (!$hasLocalImages && !empty($wp['images'])) {
                                $productData['images'] = array_map(
                                    fn($img) => $img['src'],
                                    array_slice($wp['images'], 0, 5)
                                );
                            }

                            $existing->update($productData);
                            $updated++;
                        } else {
                            // New product: import everything
                            $productData['sku'] = $wp['sku'] ?: 'WOO-' . $wp['id'];

                            if (!empty($wp['images'])) {
                                $productData['images'] = array_map(
                                    fn($img) => $img['src'],
                                    array_slice($wp['images'], 0, 5)
                                );
                            }

                            Product::create($productData);
                            $imported++;
                        }

                        // Sync variants if variable
                        if ($wp['type'] === 'variable') {
                            $this->syncVariants($wp['id'], $existing?->id ?? Product::where('woo_product_id', $wp['id'])->value('id'), $businessId);
                        }

                    } catch (\Exception $e) {
                        $errors[] = "Product #{$wp['id']}: " . $e->getMessage();
                        Log::error('WooCommerce product sync error', ['product' => $wp['id'], 'error' => $e->getMessage()]);
                    }
                }

                $page++;

            } catch (\Exception $e) {
                $errors[] = 'Page ' . $page . ': ' . $e->getMessage();
                break;
            }

        } while (count($wooProducts) === 50);

        $this->settings->update(['last_synced_at' => now()]);

        return [
            'imported' => $imported,
            'updated'  => $updated,
            'errors'   => $errors,
        ];
    }

    // =============================================
    // SYNC VARIANTS
    // =============================================
    protected function syncVariants(int $wooProductId, int $localProductId, int $businessId): void
{
    try {
        $response = $this->client()->get($this->baseUrl . "products/{$wooProductId}/variations", [
            'per_page' => 100,
        ]);

        if (!$response->successful()) return;

        // Get parent product info
        $parent = Product::find($localProductId);
        if (!$parent) return;

        // Track variation prices so the parent variable product can reflect the
        // representative regular/offer price (WooCommerce leaves the parent empty).
        $minRegular = null;
        $minSale    = null;

        foreach ($response->json() as $variant) {
            $attributes = collect($variant['attributes'])->mapWithKeys(
                fn($a) => [$a['name'] => $a['option']]
            )->toArray();

            $variantName = $parent->name . ' - ' . collect($variant['attributes'])->pluck('option')->implode(' / ');

            $vRegular = floatval($variant['regular_price'] ?: $variant['price'] ?: 0);
            $vSale    = $variant['sale_price'] ? floatval($variant['sale_price']) : null;

            if ($vRegular > 0)            $minRegular = $minRegular === null ? $vRegular : min($minRegular, $vRegular);
            if ($vSale !== null && $vSale > 0) $minSale = $minSale === null ? $vSale : min($minSale, $vSale);

            // Create as separate product
            Product::updateOrCreate(
                ['woo_product_id' => $variant['id']],
                [
                    'business_id'     => $businessId,
                    'name'            => $variantName,
                    'slug'            => \Illuminate\Support\Str::slug($variantName) . '-' . $variant['id'],
                    'sku'             => $variant['sku'] ?: 'VAR-' . $variant['id'],
                    'price'           => $vRegular,
                    'sale_price'      => $vSale,
                    'cost_price'      => $parent->cost_price ?? 0,
                    'stock'           => $variant['stock_quantity'] ?? 0,
                    'unit'            => $parent->unit ?? 'pcs',
                    'category_id'     => $parent->category_id,
                    'brand_id'        => $parent->brand_id,
                    'images'          => $variant['image']['src'] ? [$variant['image']['src']] : $parent->images,
                    'is_active'       => true,
                    'track_stock'     => true,
                    'low_stock_alert' => $parent->low_stock_alert ?? 5,
                    'woo_product_id'  => $variant['id'],
                    'woo_synced_at'   => now(),
                ]
            );

            // Also keep in product_variants for reference
            ProductVariant::updateOrCreate(
                ['woo_variant_id' => $variant['id']],
                [
                    'product_id' => $localProductId,
                    'name'       => collect($variant['attributes'])->pluck('option')->implode(' / '),
                    'sku'        => $variant['sku'] ?: 'VAR-' . $variant['id'],
                    'price'      => $vRegular,
                    'stock'      => $variant['stock_quantity'] ?? 0,
                    'attributes' => $attributes,
                ]
            );
        }

        // Reflect representative pricing on the parent so its Regular/Sale Price fields
        // are not blank for variable products.
        if ($minRegular !== null) {
            $parent->update([
                'price'      => $minRegular,
                'sale_price' => $minSale,
            ]);
        }
    } catch (\Exception $e) {
        Log::warning('Variant sync failed', ['product' => $wooProductId, 'error' => $e->getMessage()]);
    }
}

    // =============================================
    // SYNC STOCK: Fulfillo → WooCommerce
    // =============================================
    public function pushStockToWoo(int $businessId): array
    {
        $updated = 0;
        $errors  = [];

        $products = Product::where('business_id', $businessId)
            ->whereNotNull('woo_product_id')
            ->where('track_stock', true)
            ->get();

        foreach ($products as $product) {
            try {
                $response = $this->client()->put(
                    $this->baseUrl . "products/{$product->woo_product_id}",
                    [
                        'stock_quantity' => $product->stock,
                        'manage_stock'   => true,
                    ]
                );

                if ($response->successful()) {
                    $updated++;
                } else {
                    $errors[] = "Product #{$product->id}: HTTP " . $response->status();
                }
            } catch (\Exception $e) {
                $errors[] = "Product #{$product->id}: " . $e->getMessage();
            }
        }

        return ['updated' => $updated, 'errors' => $errors];
    }

    // =============================================
    // SYNC ORDERS: WooCommerce → Fulfillo
    // =============================================
    public function syncOrdersFromWoo(int $businessId, string $after = null): array
    {
        $imported = 0;
        $errors   = [];
        $page     = 1;

        $params = ['per_page' => 50, 'page' => $page, 'orderby' => 'date', 'order' => 'desc'];
        if ($after) $params['after'] = $after;

        do {
            try {
                $params['page'] = $page;
                $response = $this->client()->get($this->baseUrl . 'orders', $params);

                if (!$response->successful()) {
                    $errors[] = 'Failed to fetch orders page ' . $page;
                    break;
                }

                $wooOrders = $response->json();
                if (empty($wooOrders)) break;

                foreach ($wooOrders as $wo) {
                    try {
                        // Skip if already imported
                        if (Order::where('business_id', $businessId)->where('woo_order_id', $wo['id'])->exists()) {
                            continue;
                        }

                        $this->importWooOrder($wo, $businessId);
                        $imported++;

                    } catch (\Exception $e) {
                        $errors[] = "Order #{$wo['id']}: " . $e->getMessage();
                        Log::error('WooCommerce order import error', ['order' => $wo['id'], 'error' => $e->getMessage()]);
                    }
                }

                $page++;

            } catch (\Exception $e) {
                $errors[] = 'Page ' . $page . ': ' . $e->getMessage();
                break;
            }

        } while (count($wooOrders ?? []) === 50);

        $this->settings->update(['last_synced_at' => now()]);

        return ['imported' => $imported, 'errors' => $errors];
    }

    // =============================================
    // IMPORT SINGLE WOO ORDER
    // =============================================
    public function importWooOrder(array $wo, int $businessId): Order
    {
        // Find or create customer
        $customer = null;
        if (!empty($wo['billing']['email']) || !empty($wo['billing']['phone'])) {
            $customer = Customer::firstOrCreate(
                [
                    'business_id' => $businessId,
                    'phone'       => $wo['billing']['phone'] ?? null,
                ],
                [
                    'name'    => trim(($wo['billing']['first_name'] ?? '') . ' ' . ($wo['billing']['last_name'] ?? '')),
                    'email'   => $wo['billing']['email'] ?? null,
                    'address' => $wo['billing']['address_1'] ?? '',
                    'area'    => $wo['billing']['city'] ?? '',
                    'district'=> $wo['billing']['state'] ?? '',
                    'woo_customer_id' => $wo['customer_id'] ?? null,
                ]
            );
        }

        $shippingName = trim(($wo['shipping']['first_name'] ?? '') . ' ' . ($wo['shipping']['last_name'] ?? ''));
        $customerName = $shippingName ?: trim(($wo['billing']['first_name'] ?? '') . ' ' . ($wo['billing']['last_name'] ?? '')) ?: 'WooCommerce Customer';

        // Map WooCommerce status
        $status = match($wo['status']) {
            'pending'    => 'pending',
            'processing' => 'processing',
            'on-hold'    => 'confirmed',
            'completed'  => 'completed',
            'cancelled'  => 'cancelled',
            'refunded'   => 'returned',
            'failed'     => 'cancelled',
            default      => 'pending',
        };

        // Payment status: COD means pay-on-delivery, so it is NEVER paid at sync
        // time (the courier collects it) — regardless of Woo's status/date_paid.
        // Only a non-COD order with a real payment date counts as paid.
        $method = strtolower($wo['payment_method'] ?? '');
        $title  = strtolower($wo['payment_method_title'] ?? '');
        $isCod  = $method === 'cod' || str_contains($method, 'cod') || str_contains($title, 'cash on delivery');
        $paymentStatus = (!$isCod && !empty($wo['date_paid'] ?? $wo['date_paid_gmt'] ?? null)) ? 'paid' : 'unpaid';

        $total    = floatval($wo['total']);
        $shipping = floatval($wo['shipping_total']);
        $discount = floatval($wo['discount_total']);
        $subtotal = $total - $shipping + $discount;

        $order = Order::create([
            'business_id'       => $businessId,
            'customer_id'       => $customer?->id,
            'created_by'        => $this->systemUserId,
            'order_number'      => 'WOO-' . $wo['id'],
            'source'            => 'woocommerce',
            'status'            => $status,
            'payment_status'    => $paymentStatus,
            'payment_method'    => $wo['payment_method'] ?? 'cod',
            'customer_name'     => $customerName,
            'customer_phone'    => $wo['billing']['phone'] ?? null,
            'shipping_address'  => ($wo['shipping']['address_1'] ?? '') . ' ' . ($wo['shipping']['address_2'] ?? ''),
            'shipping_area'     => $wo['shipping']['city'] ?? '',
            'shipping_district' => $wo['shipping']['state'] ?? '',
            'subtotal'          => $subtotal,
            'discount'          => $discount,
            'shipping_charge'   => $shipping,
            'total'             => $total,
            'paid_amount'       => $paymentStatus === 'paid' ? $total : 0,
            'due_amount'        => $paymentStatus === 'paid' ? 0 : $total,
            'woo_order_id'      => $wo['id'],
            'woo_synced_at'     => now(),
        ]);

        // Create order items
        foreach ($wo['line_items'] as $item) {
            // Try to match local product
            $localProduct = Product::where('business_id', $businessId)
                ->where('woo_product_id', $item['product_id'])
                ->first();

            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $localProduct?->id,
                'product_name' => $item['name'],
                'sku'          => $item['sku'] ?? null,
                'price'        => floatval($item['price']),
                'cost_price'   => $localProduct?->cost_price ?? 0,
                'quantity'     => $item['quantity'],
                'discount'     => floatval($item['total_tax'] ?? 0),
                'total'        => floatval($item['total']),
            ]);
        }

        return $order;
    }

    // =============================================
    // UPDATE ORDER STATUS IN WOO
    // =============================================
    public function updateOrderStatus(Order $order, string $status): bool
    {
        if (!$order->woo_order_id) return false;

        $wooStatus = match($status) {
            'pending'    => 'pending',
            'confirmed'  => 'on-hold',
            'processing' => 'processing',
            'shipped'    => 'processing',
            'delivered'  => 'completed',
            'completed'  => 'completed',
            'cancelled'  => 'cancelled',
            'returned'   => 'refunded',
            default      => 'processing',
        };

        try {
            $response = $this->client()->put(
                $this->baseUrl . "orders/{$order->woo_order_id}",
                ['status' => $wooStatus]
            );

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WooCommerce order status update failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    // =============================================
    // HANDLE WEBHOOK
    // =============================================
    public function handleWebhook(string $topic, array $payload, int $businessId): void
    {
        match($topic) {
            'order.created', 'order.updated' => $this->handleOrderWebhook($payload, $businessId),
            'product.updated'                => $this->handleProductWebhook($payload, $businessId),
            default => Log::info('Unknown WooCommerce webhook topic: ' . $topic),
        };
    }

    protected function handleOrderWebhook(array $payload, int $businessId): void
    {
        $existing = Order::where('business_id', $businessId)
            ->where('woo_order_id', $payload['id'])
            ->first();

        if ($existing) {
            $status = match($payload['status']) {
                'processing' => 'processing',
                'completed'  => 'completed',
                'cancelled'  => 'cancelled',
                'refunded'   => 'returned',
                default      => $existing->status,
            };
            $existing->update(['status' => $status, 'woo_synced_at' => now()]);
        } else {
            $this->importWooOrder($payload, $businessId);
        }
    }

    protected function handleProductWebhook(array $payload, int $businessId): void
    {
        $product = Product::where('business_id', $businessId)
            ->where('woo_product_id', $payload['id'])
            ->first();

        if ($product) {
            $product->update([
                'stock'         => $payload['stock_quantity'] ?? $product->stock,
                'price'         => floatval($payload['regular_price'] ?: $payload['price'] ?: $product->price),
                'is_active'     => $payload['status'] === 'publish',
                'woo_synced_at' => now(),
            ]);
        }
    }
}
