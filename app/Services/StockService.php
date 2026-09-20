<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
        if ($order->warehouse_id) { $this->moveSelectedStock($order, false); return; }
        $warehouseId = $this->defaultWarehouseId($order->business_id);

        foreach ($order->items as $item) {
            if (!$item->product_id) continue;

            $product = Product::where('id', $item->product_id)
                ->where('business_id', $order->business_id)
                ->first();
            if (!$product || !$product->track_stock) continue;

            $before = $product->stock;
            $after  = max(0, $before - $item->quantity);

            Product::where('id', $product->id)->update(['stock' => $after]);
            $this->adjustWarehouseStock($order->business_id, $item->product_id, $warehouseId, -$item->quantity);

            $this->logAdjustment(
                business_id: $order->business_id,
                product_id:  $product->id,
                type:        'decrease',
                quantity:    $item->quantity,
                before:      $before,
                after:       $after,
                reason:      "Sale - Order #{$order->order_number}",
                warehouse_id: $warehouseId,
            );
        }
    }

    /**
     * Restore stock for each item in an order (cancel / return / update).
     */
    public function restoreForOrder(Order $order): void
    {
        if ($order->warehouse_id) { $this->moveSelectedStock($order, true); return; }
        $warehouseId = $this->defaultWarehouseId($order->business_id);

        foreach ($order->items as $item) {
            if (!$item->product_id) continue;

            $product = Product::where('id', $item->product_id)
                ->where('business_id', $order->business_id)
                ->first();
            if (!$product || !$product->track_stock) continue;

            $before = $product->stock;
            $after  = $before + $item->quantity;

            Product::where('id', $product->id)->update(['stock' => $after]);
            $this->adjustWarehouseStock($order->business_id, $item->product_id, $warehouseId, +$item->quantity);

            $this->logAdjustment(
                business_id: $order->business_id,
                product_id:  $product->id,
                type:        'return',
                quantity:    $item->quantity,
                before:      $before,
                after:       $after,
                reason:      "Return/Cancel - Order #{$order->order_number}",
                warehouse_id: $warehouseId,
            );
        }
    }

    /**
     * Partial return — put back only the quantities the customer actually sent back.
     * $lines is [product_id => qty]. Warehouse-aware and logged, like a full restore.
     */
    /**
     * Restore stock for arbitrary [product_id => qty] lines that are NOT tied to an
     * order (e.g. a manual exchange where the original invoice was never in the app).
     * Warehouse-aware and logged, like restorePartial.
     */
    public function restoreLines(int $businessId, array $lines, string $reason): int
    {
        $warehouseId = $this->defaultWarehouseId($businessId);
        $restored    = 0;

        foreach ($lines as $productId => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) continue;

            $product = Product::where('id', (int) $productId)->where('business_id', $businessId)->first();
            if (!$product || !$product->track_stock) continue;

            $before = $product->stock;
            $after  = $before + $qty;

            Product::where('id', $product->id)->update(['stock' => $after]);
            $this->adjustWarehouseStock($businessId, $product->id, $warehouseId, +$qty);

            $this->logAdjustment(
                business_id: $businessId,
                product_id:  $product->id,
                type:        'return',
                quantity:    $qty,
                before:      $before,
                after:       $after,
                reason:      $reason,
                warehouse_id: $warehouseId,
            );

            $restored += $qty;
        }

        return $restored;
    }

    public function restorePartial(Order $order, array $lines): int
    {
        if ($order->warehouse_id) { return $this->moveSelectedStock($order, true, $lines); }
        $warehouseId = $this->defaultWarehouseId($order->business_id);
        $restored    = 0;

        foreach ($lines as $productId => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) continue;

            $product = Product::where('id', (int) $productId)
                ->where('business_id', $order->business_id)
                ->first();
            if (!$product || !$product->track_stock) continue;

            $before = $product->stock;
            $after  = $before + $qty;

            Product::where('id', $product->id)->update(['stock' => $after]);
            $this->adjustWarehouseStock($order->business_id, $product->id, $warehouseId, +$qty);

            $this->logAdjustment(
                business_id: $order->business_id,
                product_id:  $product->id,
                type:        'return',
                quantity:    $qty,
                before:      $before,
                after:       $after,
                reason:      "Partial return - Order #{$order->order_number}",
                warehouse_id: $warehouseId,
            );

            $restored += $qty;
        }

        return $restored;
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

    // ──────────────────────────────────────────────
    // Warehouse-aware operations
    // ──────────────────────────────────────────────

    /**
     * Add stock to a specific warehouse. Also increments product.stock total.
     */
    public function addToWarehouse(int $bizId, int $productId, int $warehouseId, int $qty, string $reason = 'Stock received'): void
    {
        if ($qty <= 0) return;

        $product = Product::where('id', $productId)->where('business_id', $bizId)->first();
        if (!$product) return;

        $before = $product->stock;
        $after  = $before + $qty;

        DB::transaction(function () use ($bizId, $productId, $warehouseId, $qty, $before, $after, $reason) {
            Product::where('id', $productId)->update(['stock' => $after]);
            $this->adjustWarehouseStock($bizId, $productId, $warehouseId, +$qty);
            $this->logAdjustment($bizId, $productId, 'increase', $qty, $before, $after, $reason, $warehouseId);
        });
    }

    /**
     * Transfer stock between two warehouses. product.stock total is unchanged.
     */
    public function transferBetweenWarehouses(int $bizId, int $productId, int $fromId, int $toId, int $qty, string $reason = 'Warehouse transfer'): void
    {
        if ($qty <= 0 || $fromId === $toId) return;

        $product = Product::where('id', $productId)->where('business_id', $bizId)->first();
        if (!$product) return;

        DB::transaction(function () use ($bizId, $productId, $fromId, $toId, $qty, $product, $reason) {
            $this->adjustWarehouseStock($bizId, $productId, $fromId, -$qty);
            $this->adjustWarehouseStock($bizId, $productId, $toId,   +$qty);
            // product.stock total unchanged — just log the transfer
            $this->logAdjustment($bizId, $productId, 'transfer', $qty, $product->stock, $product->stock, $reason, $fromId);
        });
    }

    /**
     * Seed warehouse_stocks from existing product.stock values into the default warehouse.
     * Run once after migration via `php artisan app:seed-warehouse-stock`.
     */
    public function seedFromProductStock(int $bizId): int
    {
        $defaultId = $this->defaultWarehouseId($bizId);
        if (!$defaultId) return 0;

        $seeded = 0;
        Product::where('business_id', $bizId)->where('stock', '>', 0)->each(function ($product) use ($bizId, $defaultId, &$seeded) {
            WarehouseStock::updateOrCreate(
                ['business_id' => $bizId, 'warehouse_id' => $defaultId, 'product_id' => $product->id, 'variant_id' => null],
                ['quantity' => $product->stock]
            );
            $seeded++;
        });

        return $seeded;
    }

    public function defaultWarehouseId(int $bizId): ?int
    {
        return Warehouse::where('business_id', $bizId)->orderByDesc('is_default')->value('id');
    }

    public function hasPartialReturns(Order $order): bool
    {
        return StockAdjustment::where('business_id', $order->business_id)->where('type', 'return')
            ->where('reason', "Partial return - Order #{$order->order_number}")->exists();
    }

    private function moveSelectedStock(Order $order, bool $restoring, ?array $partial = null): int
    {
        return DB::transaction(function () use ($order, $restoring, $partial) {
            // Serialize edits, courier callbacks, and partial returns for this order.
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $warehouseId = $locked->warehouse_id;
            if (!Warehouse::where('business_id', $order->business_id)->whereKey($warehouseId)->exists()) {
                throw ValidationException::withMessages(['warehouse_id' => 'The selected stock location is unavailable.']);
            }
            if ($partial !== null && !$locked->holdsStock()) {
                throw ValidationException::withMessages(['returns' => 'This order has already been cancelled or returned.']);
            }
            $quantities = $order->items()->get()->whereNotNull('product_id')->groupBy('product_id')
                ->map(fn ($items) => (int) $items->sum('quantity'));
            $products = Product::where('business_id', $order->business_id)->whereIn('id', $quantities->keys())
                ->orderBy('id')->lockForUpdate()->get();
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
                $rows = WarehouseStock::where('business_id', $order->business_id)->where('warehouse_id', $warehouseId)
                    ->where('product_id', $product->id)->whereNull('variant_id')->orderBy('id')->lockForUpdate()->get();
                $available = (int) $rows->sum('quantity');
                if (!$restoring && ($available < $qty || $before < $qty)) {
                    throw ValidationException::withMessages(['warehouse_id' => "Insufficient stock at the selected location for {$product->name}. Available: " . min($available, $before) . "; requested: {$qty}."]);
                }
                $after = $restoring ? $before + $qty : $before - $qty;
                $product->update(['stock' => $after]);
                if ($restoring) {
                    $this->adjustWarehouseStock($order->business_id, $product->id, $warehouseId, $qty);
                } else {
                    $remaining = $qty;
                    foreach ($rows as $row) {
                        $taken = min($remaining, max(0, (int) $row->quantity));
                        $row->decrement('quantity', $taken);
                        $remaining -= $taken;
                        if ($remaining === 0) break;
                    }
                }
                // The audit row also records partial-return quantities; fail atomically if it cannot be saved.
                StockAdjustment::create([
                    'business_id' => $order->business_id, 'product_id' => $product->id,
                    'warehouse_id' => $warehouseId, 'created_by' => auth()->id() ?? $order->created_by,
                    'type' => $restoring ? 'return' : 'decrease', 'quantity' => $qty,
                    'quantity_before' => $before, 'quantity_after' => $after,
                    'reason' => ($partial !== null ? 'Partial return' : ($restoring ? 'Return/Cancel' : 'Sale')) . " - Order #{$order->order_number}",
                ]);
                $moved += $qty;
            }
            return $moved;
        });
    }

    private function adjustWarehouseStock(int $bizId, int $productId, ?int $warehouseId, int $delta): void
    {
        if (!$warehouseId || $delta === 0) return;

        $row = WarehouseStock::firstOrCreate(
            ['business_id' => $bizId, 'warehouse_id' => $warehouseId, 'product_id' => $productId, 'variant_id' => null],
            ['quantity' => 0]
        );
        $row->quantity = max(0, $row->quantity + $delta);
        $row->save();
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
