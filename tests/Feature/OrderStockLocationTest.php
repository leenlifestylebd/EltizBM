<?php

namespace Tests\Feature;

use App\Models\{Business, Order, Product, Shipment, StockAdjustment, User, Warehouse, WarehouseStock};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderStockLocationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Warehouse $warehouse;
    private Warehouse $outlet;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::preventStrayRequests();
        $business = Business::create(['name' => 'Location Test', 'slug' => uniqid('locations-'), 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create(['business_id' => $business->id, 'name' => 'Owner', 'email' => uniqid().'@example.test', 'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true]);
        $this->warehouse = Warehouse::create(['business_id' => $business->id, 'name' => 'Main Warehouse', 'is_default' => true]);
        $this->outlet = Warehouse::create(['business_id' => $business->id, 'name' => 'Outlet', 'is_default' => false]);
        $this->product = Product::create(['business_id' => $business->id, 'name' => 'Shirt - Size 40', 'slug' => 'shirt-40', 'price' => 100, 'stock' => 10, 'track_stock' => true, 'is_active' => true]);
        foreach ([[$this->warehouse, 6], [$this->outlet, 4]] as [$location, $qty]) {
            WarehouseStock::create(['business_id' => $business->id, 'warehouse_id' => $location->id, 'product_id' => $this->product->id, 'quantity' => $qty]);
        }
        $this->actingAs($this->owner);
    }

    private function payload(int $qty = 2, ?int $location = null): array
    {
        return ['total' => $qty * 100, 'customer_name' => 'Customer', 'status' => 'pending', 'payment_method' => 'cod', 'warehouse_id' => $location ?? $this->outlet->id,
            'items' => [['product_id' => $this->product->id, 'product_name' => $this->product->name, 'quantity' => $qty, 'price' => 100, 'total' => 100 * $qty]]];
    }

    private function place(int $qty = 2, ?int $location = null): Order
    {
        $this->post(route('orders.store'), $this->payload($qty, $location))->assertSessionHasNoErrors()->assertRedirect(route('orders.index'));
        return Order::latest('id')->firstOrFail();
    }

    private function assertStock(int $main, int $outlet, int $total): void
    {
        $this->assertSame($main, (int) WarehouseStock::where('warehouse_id', $this->warehouse->id)->where('product_id', $this->product->id)->sum('quantity'));
        $this->assertSame($outlet, (int) WarehouseStock::where('warehouse_id', $this->outlet->id)->where('product_id', $this->product->id)->sum('quantity'));
        $this->assertSame($total, (int) $this->product->fresh()->stock);
    }

    public function test_order_deducts_only_the_selected_outlet_and_records_location(): void
    {
        $order = $this->place();
        $this->assertSame($this->outlet->id, (int) $order->warehouse_id);
        $this->assertStock(6, 2, 8);
        $this->assertDatabaseHas('stock_adjustments', ['warehouse_id' => $this->outlet->id, 'product_id' => $this->product->id, 'type' => 'decrease', 'quantity' => 2]);
        $this->get(route('orders.show', $order))->assertOk()->assertSee('Outlet');
    }

    public function test_warehouse_can_also_supply_an_order(): void
    {
        $this->place(3, $this->warehouse->id);
        $this->assertStock(3, 4, 7);
    }

    public function test_stock_at_other_locations_cannot_cover_a_shortage(): void
    {
        $this->postJson(route('orders.store'), $this->payload(5))->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $this->assertStock(6, 4, 10);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('stock_adjustments', 0);
    }

    public function test_duplicate_lines_are_checked_as_one_total_quantity(): void
    {
        $payload = $this->payload(3);
        $payload['items'][] = $payload['items'][0];
        $this->postJson(route('orders.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $this->assertStock(6, 4, 10);
    }

    public function test_missing_location_is_rejected_when_locations_exist(): void
    {
        $payload = $this->payload();
        unset($payload['warehouse_id']);
        $this->postJson(route('orders.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $this->assertStock(6, 4, 10);
    }

    public function test_foreign_business_location_is_rejected_on_create_and_edit(): void
    {
        $other = Business::create(['name' => 'Other', 'slug' => 'other']);
        $foreign = Warehouse::create(['business_id' => $other->id, 'name' => 'Other Secret Outlet']);
        $this->postJson(route('orders.store'), $this->payload(2, $foreign->id))->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $order = $this->place();
        $this->putJson(route('orders.update', $order), $this->payload(2, $foreign->id))->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $this->get(route('orders.create'))->assertOk()->assertSee('Main Warehouse')->assertSee('Outlet')->assertDontSee('Other Secret Outlet');
        $this->assertStock(6, 2, 8);
    }

    public function test_changing_location_and_quantity_restores_old_location_first(): void
    {
        $order = $this->place();
        $this->put(route('orders.update', $order), $this->payload(3, $this->warehouse->id))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertStock(3, 4, 7);
        $this->assertSame($this->warehouse->id, (int) $order->fresh()->warehouse_id);
    }

    public function test_location_can_change_without_replacing_lines(): void
    {
        $order = $this->place();
        $payload = $this->payload(2, $this->warehouse->id);
        unset($payload['items']);
        $this->put(route('orders.update', $order), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertStock(4, 4, 8);
    }

    public function test_failed_edit_rolls_back_original_items_location_and_stock(): void
    {
        $order = $this->place();
        $this->putJson(route('orders.update', $order), $this->payload(7, $this->warehouse->id))->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $this->assertStock(6, 2, 8);
        $this->assertSame($this->outlet->id, (int) $order->fresh()->warehouse_id);
        $this->assertSame(2, (int) $order->items()->first()->quantity);
        $this->assertDatabaseCount('stock_adjustments', 1);
    }

    public function test_edit_in_same_location_can_reuse_stock_already_held_by_order(): void
    {
        $order = $this->place(4);
        $this->get(route('orders.edit', $order))->assertOk()->assertViewHas('heldStock', fn ($held) => $held[$this->product->id] === 4);
        $this->put(route('orders.update', $order), $this->payload(4))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertStock(6, 0, 6);
    }

    public function test_repeated_cancel_and_delete_restore_to_original_location_once(): void
    {
        $order = $this->place(2, $this->warehouse->id);
        $this->warehouse->update(['is_default' => false]);
        $this->outlet->update(['is_default' => true]);
        foreach (['cancelled', 'cancelled', 'returned'] as $status) {
            $this->post(route('orders.status', $order), ['status' => $status])->assertRedirect();
        }
        $this->delete(route('orders.destroy', $order))->assertRedirect();
        $this->assertStock(6, 4, 10);
        $this->assertSame(1, StockAdjustment::where('type', 'return')->count());
    }

    public function test_form_cancellation_restores_stock_and_terminal_edit_does_not_move_it(): void
    {
        $order = $this->place();
        $payload = $this->payload();
        $payload['status'] = 'cancelled';
        $this->put(route('orders.update', $order), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertStock(6, 4, 10);
        $this->put(route('orders.update', $order), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertStock(6, 4, 10);
    }

    public function test_shipped_order_cannot_change_location(): void
    {
        $order = $this->place();
        $order->update(['status' => 'shipped']);
        $payload = $this->payload(2, $this->warehouse->id);
        $payload['status'] = 'shipped';
        $this->putJson(route('orders.update', $order), $payload)->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $this->assertStock(6, 2, 8);
    }

    public function test_delete_active_order_restores_selected_source(): void
    {
        $order = $this->place();
        $this->delete(route('orders.destroy', $order))->assertRedirect();
        $this->assertStock(6, 4, 10);
    }

    public function test_steadfast_repeated_return_restores_selected_source_once(): void
    {
        config(['services.steadfast.webhook_secret' => 'test-secret']);
        $order = $this->place();
        $order->update(['status' => 'shipped']);
        Shipment::create(['business_id' => $this->owner->business_id, 'order_id' => $order->id, 'courier_name' => 'Steadfast', 'consignment_id' => 'location-test', 'status' => 'in_transit']);
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/webhook/steadfast', ['notification_type' => 'delivery_status', 'consignment_id' => 'location-test', 'status' => 'cancelled'], ['X-Webhook-Secret' => 'test-secret'])->assertOk();
        }
        $this->assertStock(6, 4, 10);
    }

    public function test_validation_retry_keeps_cart_and_location(): void
    {
        $response = $this->from(route('orders.create'))->post(route('orders.store'), $this->payload(5));
        $response->assertRedirect(route('orders.create'))->assertSessionHasErrors('warehouse_id');
        $response->assertSessionHasInput('warehouse_id', $this->outlet->id);
        $response->assertSessionHasInput('items.0.quantity', 5);
        $this->get(route('orders.create'))->assertOk()->assertSee('Shirt - Size 40');
    }

    public function test_referenced_location_cannot_be_deleted(): void
    {
        $this->place();
        $this->delete(route('settings.warehouses.destroy', $this->outlet))->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('warehouses', ['id' => $this->outlet->id]);
    }

    public function test_courier_manual_return_is_idempotent(): void
    {
        $order = $this->place();
        $order->update(['status' => 'shipped']);
        $shipment = Shipment::create(['business_id' => $this->owner->business_id, 'order_id' => $order->id, 'courier_name' => 'Local Courier', 'status' => 'in_transit']);
        for ($i = 0; $i < 2; $i++) {
            $this->put(route('courier.update', $shipment), ['status' => 'returned'])->assertRedirect();
        }
        $this->assertStock(6, 4, 10);
    }

    public function test_a_missing_stock_row_is_zero_availability(): void
    {
        WarehouseStock::where('warehouse_id', $this->outlet->id)->delete();
        $this->postJson(route('orders.store'), $this->payload())->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $this->assertSame(10, (int) $this->product->fresh()->stock);
    }

    public function test_creating_cancelled_order_does_not_deduct_or_later_restore_stock(): void
    {
        $payload = $this->payload();
        $payload['status'] = 'cancelled';
        $this->post(route('orders.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $order = Order::latest('id')->firstOrFail();
        $this->delete(route('orders.destroy', $order))->assertRedirect();
        $this->assertStock(6, 4, 10);
        $this->assertDatabaseCount('stock_adjustments', 0);
    }

    public function test_stale_courier_delivery_cannot_reopen_a_returned_order(): void
    {
        $order = $this->place();
        $shipment = Shipment::create(['business_id' => $this->owner->business_id, 'order_id' => $order->id, 'courier_name' => 'Local Courier', 'status' => 'in_transit']);
        $this->put(route('courier.update', $shipment), ['status' => 'returned'])->assertRedirect();
        $this->put(route('courier.update', $shipment), ['status' => 'delivered'])->assertRedirect()->assertSessionHas('error');
        $this->put(route('courier.update', $shipment), ['status' => 'returned'])->assertRedirect();
        $this->assertStock(6, 4, 10);
        $this->assertSame('returned', $order->fresh()->status);
    }

    public function test_partial_return_then_full_cancel_restores_each_unit_once(): void
    {
        $order = $this->place(3);
        $stock = app(\App\Services\StockService::class);
        $this->assertSame(1, $stock->restorePartial($order, [$this->product->id => 1]));
        $this->assertStock(6, 2, 8);
        $this->post(route('orders.status', $order), ['status' => 'cancelled'])->assertRedirect();
        $this->assertStock(6, 4, 10);
    }

    public function test_partial_returns_cannot_exceed_original_order_quantity(): void
    {
        $order = $this->place(2);
        $stock = app(\App\Services\StockService::class);
        $stock->restorePartial($order, [$this->product->id => 2]);
        try {
            $stock->restorePartial($order, [$this->product->id => 1]);
            $this->fail('A duplicate return must be rejected.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('returns', $exception->errors());
        }
        $this->assertStock(6, 4, 10);
    }

    public function test_production_custom_price_and_backdated_order_are_preserved(): void
    {
        $payload = $this->payload();
        $payload['order_date'] = '2026-09-01';
        $payload['items'][0]['price'] = 85;
        $payload['items'][0]['total'] = 170;
        $this->post(route('orders.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $order = Order::latest('id')->firstOrFail();
        $this->assertSame('2026-09-01', $order->created_at->toDateString());
        $this->assertSame(170.0, (float) $order->total);
        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{3,}$/', $order->order_number);
        $this->assertStock(6, 2, 8);
    }

    public function test_legacy_order_cannot_be_assigned_a_guessed_stock_source(): void
    {
        $order = $this->place();
        $order->update(['warehouse_id' => null]);
        $this->putJson(route('orders.update', $order), $this->payload(2, $this->warehouse->id))->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $this->assertStock(6, 2, 8);
    }

    public function test_untracked_product_does_not_require_location_quantity(): void
    {
        $this->product->update(['track_stock' => false]);
        $this->place(20);
        $this->assertStock(6, 4, 10);
    }

    public function test_stock_for_another_size_cannot_supply_this_product(): void
    {
        WarehouseStock::where('warehouse_id', $this->outlet->id)->update(['quantity' => 0]);
        $other = Product::create(['business_id' => $this->owner->business_id, 'name' => 'Shirt - Size 42', 'slug' => 'shirt-42', 'price' => 100, 'stock' => 9, 'track_stock' => true]);
        WarehouseStock::create(['business_id' => $this->owner->business_id, 'warehouse_id' => $this->outlet->id, 'product_id' => $other->id, 'quantity' => 9]);
        $this->postJson(route('orders.store'), $this->payload())->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $this->assertSame(9, (int) $other->fresh()->stock);
    }
}
