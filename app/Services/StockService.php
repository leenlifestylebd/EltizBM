<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StockService — Single source of truth for all inventory movements.
 *
 * Replaces duplicated stock logic in:
 *   - OrderController@store / update / updateStatus / destroy
 *   - PosController@checkout
 *   - SteadfastController@webhook (cancel restore)
 *   - CourierController@update (cancel restore)
 *   - InventoryController@storeAdjustment
 */
class StockService
{
    /**
     * Deduct stock for each item in an order.
     * Skips items with no product_id (custom items).
     * Logs each movement to stock_adjustments.
     */
    public function deductForOrder(Order $order): void
    {
        foreach ($order->items as $item) {
            if (!$item->product_id) continue;

            $product = Product::where('id', $item->product_id)
                ->where('business_id', $order->business_id)
                ->first();
            if (!$product || !$product->track_stock) continue;

            $before = $product->stock;
            $after  = max(0, $before - $item->quantity);

            Product::where('id', $product->id)->update(['stock' => $after]);

            $this->logAdjustment(
                business_id: $order->business_id,
                product_id:  $product->id,
                type:        'decrease',
                quantity:    $item->quantity,
                before:      $before,
                after:       $after,
                reason:      "Sale - Order #{$order->order_number}"
            );
        }
    }

    /**
     * Restore stock for each item in an order (cancel / return / update).
     */
    public function restoreForOrder(Order $order): void
    {
        foreach ($order->items as $item) {
            if (!$item->product_id) continue;

            $product = Product::where('id', $item->product_id)
                ->where('business_id', $order->business_id)
                ->first();
            if (!$product || !$product->track_stock) continue;

            $before = $product->stock;
            $after  = $before + $item->quantity;

            Product::where('id', $product->id)->update(['stock' => $after]);

            $this->logAdjustment(
                business_id: $order->business_id,
                product_id:  $product->id,
                type:        'return',
                quantity:    $item->quantity,
                before:      $before,
                after:       $after,
                reason:      "Return/Cancel - Order #{$order->order_number}"
            );
        }
    }

    /**
     * Direct stock adjustment (increase / decrease / damage / transfer).
     */
    public function adjust(
        int    $businessId,
        int    $productId,
        string $type,
        float  $quantity,
        string $reason = '',
        ?int   $warehouseId = null
    ): Product {
        $product = Product::findOrFail($productId);
        $before  = $product->stock;

        $after = match ($type) {
            'increase', 'return' => $before + $quantity,
            'decrease', 'damage' => max(0, $before - $quantity),
            'transfer'           => max(0, $before - $quantity),
            default              => $before,
        };

        Product::where('id', $productId)->update(['stock' => $after]);

        $this->logAdjustment($businessId, $productId, $type, $quantity, $before, $after, $reason, $warehouseId);

        return $product->fresh();
    }

    /**
     * Set stock to an exact value (used by inventory count / barcode scan).
     */
    public function setStock(int $businessId, int $productId, float $newStock, string $reason = 'Manual count'): Product
    {
        $product = Product::findOrFail($productId);
        $before  = $product->stock;
        $diff    = $newStock - $before;
        $type    = $diff >= 0 ? 'increase' : 'decrease';

        Product::where('id', $productId)->update(['stock' => $newStock]);

        $this->logAdjustment($businessId, $productId, $type, abs($diff), $before, $newStock, $reason);

        return $product->fresh();
    }

    /**
     * Internal: write to stock_adjustments audit table.
     */
    private function logAdjustment(
        int     $business_id,
        int     $product_id,
        string  $type,
        float   $quantity,
        float   $before,
        float   $after,
        string  $reason = '',
        ?int    $warehouse_id = null
    ): void {
        try {
            StockAdjustment::create([
                'business_id'     => $business_id,
                'product_id'      => $product_id,
                'warehouse_id'    => $warehouse_id,
                'created_by'      => auth()->id(),
                'type'            => $type,
                'quantity'        => $quantity,
                'quantity_before' => $before,
                'quantity_after'  => $after,
                'reason'          => $reason,
            ]);
        } catch (\Exception $e) {
            Log::warning('StockAdjustment log failed', ['error' => $e->getMessage()]);
        }
    }
}
