<?php

namespace Tests\Feature;

use App\Models\{Business, Order, Product, PurchaseOrder, PurchaseOrderItem, Shipment, StockAdjustment, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Stock is tracked on a single "Main Warehouse" per business: products.stock is
 * the only authoritative quantity. These tests cover deduct, restore, the
 * increase/decrease adjustment path, and purchase-order receiving.
 */
class OrderStockTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::preventStrayRequests();
        $business = Business::create(['name' => 'Stock Test', 'slug' => uniqid('stock-'), 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create(['business_id' => $business->id, 'name' => 'Owner', 'email' => uniqid().'@example.test', 'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true]);
        $this->product = Product::create(['business_id' => $business->id, 'name' => 'Shirt - Size 40', 'slug' => 'shirt-40', 'price' => 100, 'stock' => 10, 'track_stock' => true, 'is_active' => true]);
        $this->actingAs($this->owner);
    }

    private function payload(int $qty = 2, string $status = 'pending'): array
    {
        return ['customer_name' => 'Customer', 'status' => $status, 'payment_method' => 'cod', 'total' => 100 * $qty,
            'items' => [['product_id' => $this->product->id, 'product_name' => $this->product->name, 'quantity' => $qty, 'price' => 100, 'total' => 100 * $qty]]];
    }

    private function place(int $qty = 2, string $status = 'pending'): Order
    {
        $this->post(route('orders.store'), $this->payload($qty, $status))->assertSessionHasNoErrors()->assertRedirect(route('orders.index'));
        return Order::latest('id')->firstOrFail();
    }

    private function assertStock(int $expected): void
    {
        $this->assertSame($expected, (int) $this->product->fresh()->stock);
    }

    // â”€â”€ deduct / restore â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function test_order_deducts_stock_and_records_the_movement(): void
    {
        $order = $this->place(2);
        $this->assertStock(8);
        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $this->product->id, 'type' => 'decrease', 'quantity' => 2,
            'quantity_before' => 10, 'quantity_after' => 8,
        ]);
        $this->get(route('orders.show', $order))->assertOk()->assertSee($this->product->name);
    }

    public function test_insufficient_stock_is_rejected(): void
    {
        $this->postJson(route('orders.store'), $this->payload(11))->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');
        $this->assertStock(10);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('stock_adjustments', 0);
    }

    public function test_duplicate_lines_are_checked_as_one_total_quantity(): void
    {
        // Two lines of 6 each pass a per-line check but exceed the 10 in stock.
        $payload = $this->payload(6);
        $payload['items'][] = $payload['items'][0];
        $this->postJson(route('orders.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');
        $this->assertStock(10);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_changing_quantity_restores_the_previous_stock_first(): void
    {
        $order = $this->place(2);
        $this->assertStock(8);

        $this->put(route('orders.update', $order), $this->payload(3))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertStock(7);
    }

    public function test_failed_edit_rolls_back_original_items_and_stock(): void
    {
        $order = $this->place(2);
        // 11 exceeds the 10 in stock even after the order's own 2 are restored,
        // so validation fails and the whole edit must roll back.
        $this->putJson(route('orders.update', $order), $this->payload(11))->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');
        $this->assertStock(8);
        $this->assertSame(2, (int) $order->items()->first()->quantity);
        $this->assertDatabaseCount('stock_adjustments', 1);
    }

    public function test_edit_can_reuse_stock_already_held_by_the_order(): void
    {
        $order = $this->place(4);
        $this->assertStock(6);

        $this->get(route('orders.edit', $order))->assertOk()->assertViewHas('heldStock', fn ($held) => $held[$this->product->id] === 4);

        // The 4 units are restored before the new 4 are deducted, so stock must
        // stay at 6 rather than dropping to 2.
        $this->put(route('orders.update', $order), $this->payload(4))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertStock(6);
    }

    public function test_repeated_cancel_and_delete_restore_stock_only_once(): void
    {
        $order = $this->place(2);
        $this->assertStock(8);

        foreach (['cancelled', 'cancelled', 'returned'] as $status) {
            $this->post(route('orders.status', $order), ['status' => $status])->assertRedirect();
        }
        $this->delete(route('orders.destroy', $order))->assertRedirect();

        $this->assertStock(10);
        $this->assertSame(1, StockAdjustment::where('type', 'return')->count());
    }

    public function test_form_cancellation_restores_stock_and_terminal_edit_does_not_move_it(): void
    {
        $order = $this->place();
        $payload = $this->payload();
        $payload['status'] = 'cancelled';

        $this->put(route('orders.update', $order), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertStock(10);

        $this->put(route('orders.update', $order), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertStock(10);
    }

    public function test_delete_active_order_restores_stock(): void
    {
        $order = $this->place(2);
        $this->assertStock(8);

        $this->delete(route('orders.destroy', $order))->assertRedirect();
        $this->assertStock(10);
    }

    public function test_creating_cancelled_order_does_not_deduct_or_later_restore_stock(): void
    {
        $this->post(route('orders.store'), $this->payload(2, 'cancelled'))->assertRedirect()->assertSessionHasNoErrors();
        $order = Order::latest('id')->firstOrFail();

        $this->delete(route('orders.destroy', $order))->assertRedirect();
        $this->assertStock(10);
        $this->assertDatabaseCount('stock_adjustments', 0);
    }

    public function test_steadfast_repeated_return_restores_stock_once(): void
    {
        config(['services.steadfast.webhook_secret' => 'test-secret']);
        $order = $this->place();
        $order->update(['status' => 'shipped']);
        Shipment::create(['business_id' => $this->owner->business_id, 'order_id' => $order->id, 'courier_name' => 'Steadfast', 'consignment_id' => 'stock-test', 'status' => 'in_transit']);

        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/webhook/steadfast', ['notification_type' => 'delivery_status', 'consignment_id' => 'stock-test', 'status' => 'cancelled'], ['X-Webhook-Secret' => 'test-secret'])->assertOk();
        }
        $this->assertStock(10);
    }

    public function test_courier_manual_return_is_idempotent(): void
    {
        $order = $this->place();
        $order->update(['status' => 'shipped']);
        $shipment = Shipment::create(['business_id' => $this->owner->business_id, 'order_id' => $order->id, 'courier_name' => 'Local Courier', 'status' => 'in_transit']);

        for ($i = 0; $i < 2; $i++) {
            $this->put(route('courier.update', $shipment), ['status' => 'returned'])->assertRedirect();
        }
        $this->assertStock(10);
    }

    public function test_stale_courier_delivery_cannot_reopen_a_returned_order(): void
    {
        $order = $this->place();
        $shipment = Shipment::create(['business_id' => $this->owner->business_id, 'order_id' => $order->id, 'courier_name' => 'Local Courier', 'status' => 'in_transit']);

        $this->put(route('courier.update', $shipment), ['status' => 'returned'])->assertRedirect();
        $this->put(route('courier.update', $shipment), ['status' => 'delivered'])->assertRedirect()->assertSessionHas('error');
        $this->put(route('courier.update', $shipment), ['status' => 'returned'])->assertRedirect();

        $this->assertStock(10);
        $this->assertSame('returned', $order->fresh()->status);
    }

    // â”€â”€ increase / decrease â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function test_quick_adjust_increases_and_decreases_stock(): void
    {
        $this->postJson(route('inventory.quick-adjust'), ['product_id' => $this->product->id, 'delta' => 5])
            ->assertOk()->assertJson(['stock' => 15, 'changed' => true]);
        $this->assertStock(15);
        $this->assertDatabaseHas('stock_adjustments', ['product_id' => $this->product->id, 'type' => 'increase', 'quantity' => 5, 'quantity_after' => 15]);

        $this->postJson(route('inventory.quick-adjust'), ['product_id' => $this->product->id, 'delta' => -3])
            ->assertOk()->assertJson(['stock' => 12, 'changed' => true]);
        $this->assertStock(12);
        $this->assertDatabaseHas('stock_adjustments', ['product_id' => $this->product->id, 'type' => 'decrease', 'quantity' => 3, 'quantity_after' => 12]);
    }

    public function test_quick_adjust_never_drives_stock_below_zero(): void
    {
        $this->postJson(route('inventory.quick-adjust'), ['product_id' => $this->product->id, 'delta' => -999])
            ->assertOk()->assertJson(['stock' => 0, 'changed' => true]);
        $this->assertStock(0);
    }

    public function test_stock_adjustment_can_set_an_exact_count(): void
    {
        $this->post(route('inventory.adjustments.store'), [
            'product_id' => $this->product->id,
            'type' => 'increase',
            'quantity' => 7,
            'new_stock' => 40,
            'source' => 'stock_levels',
        ])->assertRedirect();

        // The counted quantity (40) wins; the audit records the real delta of 30.
        $this->assertStock(40);
        $this->assertDatabaseHas('stock_adjustments', ['product_id' => $this->product->id, 'type' => 'increase', 'quantity' => 30, 'quantity_before' => 10, 'quantity_after' => 40]);
    }

    public function test_purchase_order_receipt_adds_stock(): void
    {
        $po = PurchaseOrder::create(['business_id' => $this->owner->business_id, 'created_by' => $this->owner->id, 'po_number' => 'PO-TEST-1', 'status' => 'ordered', 'total' => 400]);
        PurchaseOrderItem::create(['purchase_order_id' => $po->id, 'product_id' => $this->product->id, 'quantity' => 25, 'cost_price' => 16, 'total' => 400]);

        $this->post(route('inventory.purchase-orders.store'), ['action' => 'mark_received', 'po_id' => $po->id])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertStock(35);
        $this->assertDatabaseHas('stock_adjustments', ['product_id' => $this->product->id, 'type' => 'increase', 'quantity' => 25]);
    }

    public function test_purchase_order_cannot_be_received_twice(): void
    {
        $po = PurchaseOrder::create(['business_id' => $this->owner->business_id, 'created_by' => $this->owner->id, 'po_number' => 'PO-TEST-2', 'status' => 'ordered', 'total' => 200]);
        PurchaseOrderItem::create(['purchase_order_id' => $po->id, 'product_id' => $this->product->id, 'quantity' => 20, 'cost_price' => 10, 'total' => 200]);

        $this->post(route('inventory.purchase-orders.store'), ['action' => 'mark_received', 'po_id' => $po->id])->assertRedirect();
        $this->post(route('inventory.purchase-orders.store'), ['action' => 'mark_received', 'po_id' => $po->id])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertStock(30);
    }

    // â”€â”€ isolation & edge cases â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function test_untracked_product_is_not_blocked_by_stock_level(): void
    {
        $this->product->update(['track_stock' => false]);
        $this->place(20);
        $this->assertStock(10);
        $this->assertDatabaseCount('stock_adjustments', 0);
    }

    public function test_stock_for_another_sku_cannot_supply_this_product(): void
    {
        $this->product->update(['stock' => 0]);
        $other = Product::create(['business_id' => $this->owner->business_id, 'name' => 'Shirt - Size 42', 'slug' => 'shirt-42', 'price' => 100, 'stock' => 9, 'track_stock' => true]);

        $this->postJson(route('orders.store'), $this->payload(1))->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');
        $this->assertSame(9, (int) $other->fresh()->stock);
    }

    public function test_validation_retry_keeps_the_cart(): void
    {
        $response = $this->from(route('orders.create'))->post(route('orders.store'), $this->payload(11));
        $response->assertRedirect(route('orders.create'))->assertSessionHasErrors('items.0.quantity');
        $response->assertSessionHasInput('items.0.quantity', 11);
        $this->get(route('orders.create'))->assertOk()->assertSee('Shirt - Size 40');
    }

    public function test_another_business_product_cannot_be_ordered(): void
    {
        $other = Business::create(['name' => 'Other', 'slug' => 'other-' . uniqid()]);
        $foreign = Product::create(['business_id' => $other->id, 'name' => 'Foreign Shirt', 'slug' => 'foreign-shirt', 'price' => 100, 'stock' => 50, 'track_stock' => true]);

        $this->post(route('orders.store'), $this->payload(2))->assertRedirect()->assertSessionHasNoErrors(); // sanity: own product works

        $payload = $this->payload(2);
        $payload['items'][0]['product_id'] = $foreign->id;
        $this->postJson(route('orders.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.product_id');
        $this->assertSame(50, (int) $foreign->fresh()->stock);
    }

    public function test_another_business_order_cannot_be_edited(): void
    {
        $order = $this->place();
        $this->post(route('orders.store'), $this->payload(2))->assertRedirect();

        $foreignOrder = Order::latest('id')->firstOrFail();
        $other = Business::create(['name' => 'Other', 'slug' => 'other-' . uniqid()]);
        $foreignOrder->update(['business_id' => $other->id]);

        $this->put(route('orders.update', $foreignOrder), $this->payload(5))->assertForbidden();
        $this->assertStock(6); // only our own two orders moved stock
    }

    public function test_single_warehouse_pages_render_without_warehouse_routes(): void
    {
        $this->assertDatabaseMissing('warehouses', ['business_id' => $this->owner->business_id]);

        $this->get(route('inventory.stock-levels'))->assertOk();
        $this->get(route('inventory.adjustments'))->assertOk();
        $this->get(route('inventory.overview'))->assertOk()->assertDontSee('Transfers');
        $this->get(route('orders.create'))->assertOk()->assertDontSee('Select warehouse / outlet');
    }

    public function test_removed_warehouse_routes_are_gone(): void
    {
        foreach (['inventory.transfers', 'inventory.warehouse-stock', 'settings.warehouses'] as $name) {
            $this->assertFalse(
                \Illuminate\Support\Facades\Route::has($name),
                "Route [{$name}] should no longer exist."
            );
        }
    }
}