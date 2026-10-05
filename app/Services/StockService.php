<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
        $this->moveStock($order, restoring: false);
    }

    /**
     * Restore stock for each item in an order (cancel / return / update).
     */
    public function restoreForOrder(Order $order): void
    {
        $this->moveStock($order, restoring: true);
    }

    /**
     * Partial return — put back only the quantities the customer actually sent back.
     * $lines is [product_id => qty]. Logged, like a full restore.
     */
    public function restorePartial(Order $order, array $lines): int
    {
        return $this->moveStock($order, restoring: true, partial: $lines);
    }

    /**
     * Restore stock for arbitrary [product_id => qty] lines that are NOT tied to an
     * order (e.g. a manual exchange where the original invoice was never in the app).
     */
    public function restoreLines(int $businessId, array $lines, string $reason): int
    {
        $restored = 0;

        foreach ($lines as $productId => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) continue;

            $product = Product::where('id', (int) $productId)->where('business_id', $businessId)->first();
            if (!$product || !$product->track_stock) continue;

            $before = $product->stock;
            $after  = $before + $qty;

            Product::where('id', $product->id)->update(['stock' => $after]);

            $this->logAdjustment(
                business_id: $businessId,
                product_id:  $product->id,
                type:        'return',
                quantity:    $qty,
                before:      $before,
                after:       $after,
                reason:      $reason,
            );

            $restored += $qty;
        }

        return $restored;
    }

    /**
     * Direct stock adjustment (increase / decrease / damage).
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

    public function hasPartialReturns(Order $order): bool
    {
        return StockAdjustment::where('business_id', $order->business_id)->where('type', 'return')
            ->where('reason', "Partial return - Order #{$order->order_number}")->exists();
    }

    /**
     * Apply one stock movement for an order inside a transaction.
     *
     * Locks the order row to serialize edits, courier callbacks and partial
     * returns, then locks the affected products in a stable order. Duplicate lines
     * are aggregated so the availability check and the deduction agree.
     */
    private function moveStock(Order $order, bool $restoring, ?array $partial = null): int
    {
        return DB::transaction(function () use ($order, $restoring, $partial) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($partial !== null && !$locked->holdsStock()) {
                throw ValidationException::withMessages(['returns' => 'This order has already been cancelled or returned.']);
            }

            $quantities = $order->items()->get()->whereNotNull('product_id')->groupBy('product_id')
                ->map(fn ($items) => (int) $items->sum('quantity'));
            $products = Product::where('business_id', $order->business_id)->whereIn('id', $quantities->keys())
                ->orderBy('id')->lockForUpdate()->get();

            // Units already given back by earlier partial returns are not restored twice.
            $returned = StockAdjustment::where('business_id', $order->business_id)->where('type', 'return')
                ->where('reason', "Partial return - Order #{$order->order_number}")
                ->selectRaw('product_id, SUM(quantity) as quantity')->groupBy('product_id')->pluck('quantity', 'product_id');

            $moved = 0;
            foreach ($products as $product) {
                if (!$product->track_stock) continue;
                $qty = $quantities[$product->id];
                if ($restoring) $qty = max(0, $qty - (int) ($returned[$product->id] ?? 0));

                if ($partial !== null) {
                    $requested = (int) ($partial[$product->id] ?? 0);
                    if ($requested > $qty) {
                        throw ValidationException::withMessages(['returns' => "Only {$qty} unreturned unit(s) of {$product->name} remain in this order."]);
                    }
                    $qty = max(0, $requested);
                }
                if ($qty === 0) continue;

                $before = (int) $product->stock;
                $after  = $restoring ? $before + $qty : max(0, $before - $qty);
                $product->update(['stock' => $after]);

                // The audit row also records partial-return quantities; fail atomically if it cannot be saved.
                StockAdjustment::create([
                    'business_id' => $order->business_id, 'product_id' => $product->id,
                    'created_by' => auth()->id() ?? $order->created_by,
                    'type' => $restoring ? 'return' : 'decrease', 'quantity' => $qty,
                    'quantity_before' => $before, 'quantity_after' => $after,
                    'reason' => ($partial !== null ? 'Partial return' : ($restoring ? 'Return/Cancel' : 'Sale')) . " - Order #{$order->order_number}",
                ]);

                $moved += $qty;
            }

            return $moved;
        });
    }

    // ──────────────────────────────────────────────

    /**
     * Internal: write to stock_adjustments audit table.
     */
    private function logAdjustment(
        int    $business_id,
        int    $product_id,
        string $type,
        float  $quantity,
        float  $before,
        float  $after,
        string $reason = ''
    ): void {
        try {
            StockAdjustment::create([
                'business_id'     => $business_id,
                'product_id'      => $product_id,
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