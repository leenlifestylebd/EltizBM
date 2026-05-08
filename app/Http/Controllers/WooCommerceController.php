<?php

namespace App\Http\Controllers;

use App\Models\WoocommerceSetting;
use App\Services\WooCommerceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WooCommerceController extends Controller
{
    // =============================================
    // SETTINGS PAGE
    // =============================================
    public function settings()
    {
        $bizId       = auth()->user()->business_id;
        $wooSettings = WoocommerceSetting::where('business_id', $bizId)->first();
        return view('integrations.woocommerce', compact('wooSettings'));
    }

    // =============================================
    // SAVE SETTINGS
    // =============================================
    public function saveSettings(Request $request)
    {
        $request->validate([
            'store_url'       => 'required|url',
            'consumer_key'    => 'required|string',
            'consumer_secret' => 'required|string',
        ]);

        $bizId = auth()->user()->business_id;

        WoocommerceSetting::updateOrCreate(
            ['business_id' => $bizId],
            [
                'store_url'       => rtrim($request->store_url, '/'),
                'consumer_key'    => $request->consumer_key,
                'consumer_secret' => $request->consumer_secret,
                'is_active'       => true,
                'sync_products'   => $request->boolean('sync_products', true),
                'sync_orders'     => $request->boolean('sync_orders', true),
                'sync_stock'      => $request->boolean('sync_stock', true),
                'webhook_secret'  => $request->webhook_secret ?: \Illuminate\Support\Str::random(32),
            ]
        );

        return back()->with('success', 'WooCommerce settings saved!');
    }

    // =============================================
    // TEST CONNECTION
    // =============================================
    public function testConnection(Request $request)
    {
        $bizId       = auth()->user()->business_id;
        $wooSettings = WoocommerceSetting::where('business_id', $bizId)->first();

        if (!$wooSettings) {
            return response()->json(['success' => false, 'message' => 'Settings not saved yet.']);
        }

        $service = new WooCommerceService($wooSettings);
        $result  = $service->testConnection();

        return response()->json($result);
    }

    // =============================================
    // SYNC ALL
    // =============================================
    public function syncAll(Request $request)
    {
        $bizId       = auth()->user()->business_id;
        $wooSettings = WoocommerceSetting::where('business_id', $bizId)->first();

        if (!$wooSettings || !$wooSettings->is_active) {
            return back()->with('error', 'WooCommerce not connected.');
        }

        $service = new WooCommerceService($wooSettings);
        $results = [];

        $syncType = $request->sync_type ?? 'full';

        if (in_array($syncType, ['pull_all', 'full']) && $wooSettings->sync_products) {
            $productResult = $service->syncProductsFromWoo($bizId);
            $results['products'] = $productResult;
        }

        if (in_array($syncType, ['pull_all', 'full']) && $wooSettings->sync_orders) {
            $after = $wooSettings->last_synced_at?->toIso8601String();
            $orderResult = $service->syncOrdersFromWoo($bizId, $after);
            $results['orders'] = $orderResult;
        }

        if (in_array($syncType, ['push_all', 'full']) && $wooSettings->sync_stock) {
            $stockResult = $service->pushStockToWoo($bizId);
            $results['stock'] = $stockResult;
        }

        // Build message
        $msg = '';
        if (isset($results['products'])) {
            $msg .= "Products: {$results['products']['imported']} imported, {$results['products']['updated']} updated. ";
        }
        if (isset($results['orders'])) {
            $msg .= "Orders: {$results['orders']['imported']} imported. ";
        }
        if (isset($results['stock'])) {
            $msg .= "Stock: {$results['stock']['updated']} pushed to WooCommerce.";
        }

        return back()->with('success', trim($msg) ?: 'Sync completed!');
    }

    // =============================================
    // WEBHOOK (no auth, called by WooCommerce)
    // =============================================
    public function webhook(Request $request)
    {
        $topic     = $request->header('X-WC-Webhook-Topic');
       $signature = $request->header('X-WC-Webhook-Signature');
        $source    = $request->header('X-WC-Webhook-Source');

        Log::info('WooCommerce webhook received', ['topic' => $topic, 'source' => $source]);

        // Find business by store URL
        $wooSettings = WoocommerceSetting::where('store_url', 'like', '%' . parse_url($source, PHP_URL_HOST) . '%')
            ->where('is_active', true)
            ->first();

        if (!$wooSettings) {
            Log::warning('WooCommerce webhook: no matching store found for ' . $source);
            return response()->json(['message' => 'Store not found'], 404);
        }

        // Verify signature (optional)
        if ($wooSettings->webhook_secret && $signature) {
            $payload   = $request->getContent();
            $hash      = base64_encode(hash_hmac('sha256', $payload, $wooSettings->webhook_secret, true));
            if (!hash_equals($hash, $signature)) {
                Log::warning('WooCommerce webhook: invalid signature');
                return response()->json(['message' => 'Invalid signature'], 401);
            }
        }

        try {
            $service = new WooCommerceService($wooSettings);
            $service->handleWebhook($topic ?? 'unknown', $request->all(), $wooSettings->business_id);
            return response()->json(['message' => 'Webhook processed']);
        } catch (\Exception $e) {
            Log::error('Webhook processing error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error'], 500);
        }
    }
}
