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
 * Stock is tracked on a single "Main Warehouse" per business: products.stock is
 * the only authoritative quantity. The warehouses / warehouse_stocks tables are
 * retained for historical records but are no longer read or written.
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
        $this->moveOrderStock($order, restoring: false);
    }

    public function restoreForOrder(Order $order): void
    {
        $this->moveOrderStock($order, restoring: true);
    }

    /**
     * Restore specific returned quantities (e.g. a partial RMA), business-scoped,
     * row-locked and audited the same way as an order restore.
     *
     * @param  array<int,int>  $quantitiesByProductId
     */
    public function restoreReturned(Order $order, array $quantitiesByProductId, string $reason = 'Customer return'): void
    {
        $quantities = collect($quantitiesByProductId)
            ->filter(fn ($qty, $id) => (int) $id > 0 && (int) $qty > 0);

        if ($quantities->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($order, $quantities, $reason) {
            $products = Product::where('business_id', $order->business_id)
                ->whereIn('id', $quantities->keys())
                ->orderBy('id')->lockForUpdate()->get();

            foreach ($products as $product) {
                if (!$product->track_stock) continue;
                $qty = (int) $quantities[$product->id];
                if ($qty <= 0) continue;

                $before = (int) $product->stock;
                $after  = $before + $qty;
                $product->update(['stock' => $after]);

                $this->logAdjustment(
                    business_id: $order->business_id,
                    product_id: $product->id,
                    type: 'return',
                    quantity: $qty,
                    before: $before,
                    after: $after,
                    reason: $reason,
                    created_by: $order->created_by,
                );
            }
        });
    }

    private function moveOrderStock(Order $order, bool $restoring): void
    {
        DB::transaction(function () use ($order, $restoring) {
            // Lock in product order and aggregate duplicate lines before applying the movement.
            $quantities = $order->items->whereNotNull('product_id')->groupBy('product_id')
                ->map(fn ($items) => (int) $items->sum('quantity'));
            $products = Product::where('business_id', $order->business_id)
                ->whereIn('id', $quantities->keys())->orderBy('id')->lockForUpdate()->get();

            foreach ($products as $product) {
                if (!$product->track_stock) continue;
                $qty = $quantities[$product->id];
                $before = (int) $product->stock;

                $after = $restoring ? $before + $qty : max(0, $before - $qty);
                $product->update(['stock' => $after]);

                $this->logAdjustment(
                    business_id: $order->business_id,
                    product_id: $product->id,
                    type: $restoring ? 'return' : 'decrease',
                    quantity: $qty,
                    before: $before,
                    after: $after,
                    reason: ($restoring ? 'Return/Cancel' : 'Sale') . " - Order #{$order->order_number}",
                    created_by: $order->created_by,
                );
            }
        });
    }

    /**
     * Direct stock adjustment (increase / decrease / damage / return).
     */
    public function adjust(
        int    $businessId,
        int    $productId,
        string $type,
        float  $quantity,
        string $reason = ''
    ): Product {
        $product = Product::where('id', $productId)->where('business_id', $businessId)->firstOrFail();
        $before  = $product->stock;

        $after = match ($type) {
            'increase', 'return' => $before + $quantity,
            'decrease', 'damage' => max(0, $before - $quantity),
            default              => $before,
        };

        $product->update(['stock' => $after]);

        $this->logAdjustment($businessId, $productId, $type, $quantity, $before, $after, $reason);

        return $product->fresh();
    }

    /**
     * Set stock to an exact value (used by inventory count / barcode scan).
     */
    public function setStock(int $businessId, int $productId, float $newStock, string $reason = 'Manual count'): Product
    {
        $product = Product::where('id', $productId)->where('business_id', $businessId)->firstOrFail();
        $before  = $product->stock;
        $diff    = $newStock - $before;
        $type    = $diff >= 0 ? 'increase' : 'decrease';

        $product->update(['stock' => $newStock]);

        $this->logAdjustment($businessId, $productId, $type, abs($diff), $before, $newStock, $reason);

        return $product->fresh();
    }

    // ──────────────────────────────────────────────

    /**
     * Receive stock into the main warehouse (purchase order, opening stock).
     */
    public function addStock(int $bizId, int $productId, int $qty, string $reason = 'Stock received'): void
    {
        if ($qty <= 0) return;

        $product = Product::where('id', $productId)->where('business_id', $bizId)->first();
        if (!$product) return;

        $before = $product->stock;
        $after  = $before + $qty;

        DB::transaction(function () use ($product, $bizId, $qty, $before, $after, $reason) {
            $product->update(['stock' => $after]);
            $this->logAdjustment($bizId, $product->id, 'increase', $qty, $before, $after, $reason);
        });
    }

    // ──────────────────────────────────────────────

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
        ?int    $created_by = null
    ): void {
        try {
            StockAdjustment::create([
                'business_id'     => $business_id,
                'product_id'      => $product_id,
                'created_by'      => $created_by ?? auth()->id(),
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
