<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Shipment;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CriticalRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_without_orders_permission_cannot_open_orders(): void
    {
        [, $staff] = $this->businessAndUser('staff', []);

        $this->actingAs($staff)->get(route('orders.index'))->assertForbidden();
    }

    public function test_local_admin_can_login_and_open_core_pages(): void
    {
        [$business, $owner] = $this->businessAndUser('owner');

        $this->post(route('login'), [
            'email' => $owner->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($owner);

        foreach ([
            route('dashboard'),
            route('orders.index'),
            route('orders.create'),
            route('tasks.index'),
            route('inventory.purchase-orders'),
            route('settings.business'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_manual_order_uses_catalog_price_and_ignores_tampered_totals(): void
    {
        [$business, $owner] = $this->businessAndUser('owner');
        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Secure Product',
            'slug' => 'secure-product',
            'price' => 100,
            'cost_price' => 60,
            'stock' => 10,
            'track_stock' => true,
        ]);

        $response = $this->actingAs($owner)->post(route('orders.store'), [
            'customer_name' => 'Customer',
            'status' => 'pending',
            'payment_method' => 'cash',
            'paid_amount' => 0,
            'total' => 1,
            'items' => [[
                'product_id' => $product->id,
                'product_name' => 'Tampered name',
                'price' => 0.50,
                'cost_price' => 0,
                'quantity' => 2,
                'total' => 1,
            ]],
        ]);

        $response->assertRedirect(route('orders.index'));
        $order = Order::latest('id')->firstOrFail();
        $item = $order->items()->firstOrFail();
        $this->assertSame(200.0, (float) $order->total);
        $this->assertSame(100.0, (float) $item->price);
        $this->assertSame(60.0, (float) $item->cost_price);
        $this->assertSame('Secure Product', $item->product_name);
        $this->assertSame(8, (int) $product->fresh()->stock);
    }

    public function test_steadfast_webhook_fails_closed_without_secret(): void
    {
        config(['services.steadfast.webhook_secret' => null]);

        $this->postJson('/webhook/steadfast', [
            'notification_type' => 'delivery_status',
            'consignment_id' => 'C-1',
            'status' => 'delivered',
        ])->assertStatus(503);
    }

    public function test_order_update_ignores_tampered_price_and_paid_amount(): void
    {
        [$business, $owner] = $this->businessAndUser('owner');
        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Catalog Product',
            'slug' => 'catalog-product',
            'price' => 100,
            'cost_price' => 50,
            'stock' => 8,
            'track_stock' => true,
        ]);
        $order = Order::create([
            'business_id' => $business->id,
            'created_by' => $owner->id,
            'source' => 'manual',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
            'customer_name' => 'Customer',
            'subtotal' => 200,
            'total' => 200,
            'paid_amount' => 0,
            'due_amount' => 200,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100,
            'cost_price' => 50,
            'quantity' => 2,
            'total' => 200,
        ]);

        $this->actingAs($owner)->put(route('orders.update', $order), [
            'customer_name' => 'Customer',
            'status' => 'pending',
            'payment_method' => 'cash',
            'paid_amount' => 999,
            'items' => [[
                'product_id' => $product->id,
                'product_name' => 'Changed',
                'price' => 1,
                'cost_price' => 1,
                'quantity' => 1,
                'total' => 1,
            ]],
        ])->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertSame(100.0, (float) $order->total);
        $this->assertSame(0.0, (float) $order->paid_amount);
        $this->assertSame(100.0, (float) $order->items()->firstOrFail()->price);
        $this->assertSame(9, (int) $product->fresh()->stock);
    }

    public function test_task_page_uses_sqlite_compatible_priority_ordering(): void
    {
        [$business, $owner] = $this->businessAndUser('owner');
        Task::create([
            'business_id' => $business->id,
            'created_by' => $owner->id,
            'title' => 'Urgent task',
            'priority' => 'urgent',
            'status' => 'todo',
        ]);

        $this->actingAs($owner)->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Urgent task');
    }

    public function test_purchase_order_payment_route_records_the_payment(): void
    {
        [$business, $owner] = $this->businessAndUser('owner');
        $purchaseOrder = PurchaseOrder::create([
            'business_id' => $business->id,
            'created_by' => $owner->id,
            'po_number' => 'PO-TEST-1',
            'status' => 'ordered',
            'payment_status' => 'unpaid',
            'total' => 500,
            'paid_amount' => 0,
        ]);

        $this->actingAs($owner)->post(route('inventory.purchase-orders.pay', $purchaseOrder), [
            'amount' => 200,
            'payment_method' => 'cash',
            'payment_date' => '2026-08-25',
        ])->assertRedirect();

        $this->assertSame(200.0, (float) $purchaseOrder->fresh()->paid_amount);
        $this->assertDatabaseHas('transactions', [
            'business_id' => $business->id,
            'reference' => 'PO-PAY-' . $purchaseOrder->id,
            'type' => 'expense',
            'amount' => 200,
        ]);
    }

    public function test_replayed_steadfast_cancellation_restores_stock_only_once(): void
    {
        config(['services.steadfast.webhook_secret' => 'test-secret']);
        [$business, $owner] = $this->businessAndUser('owner');
        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Returned Product',
            'slug' => 'returned-product',
            'price' => 100,
            'stock' => 8,
            'track_stock' => true,
        ]);
        $order = Order::create([
            'business_id' => $business->id,
            'created_by' => $owner->id,
            'source' => 'manual',
            'status' => 'shipped',
            'payment_status' => 'paid',
            'payment_method' => 'cod',
            'customer_name' => 'Customer',
            'subtotal' => 200,
            'total' => 200,
            'paid_amount' => 200,
            'due_amount' => 0,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100,
            'quantity' => 2,
            'total' => 200,
        ]);
        $shipment = Shipment::create([
            'business_id' => $business->id,
            'order_id' => $order->id,
            'courier_name' => 'Steadfast',
            'tracking_id' => 'T-1',
            'consignment_id' => 'C-1',
            'status' => 'in_transit',
        ]);

        $payload = [
            'notification_type' => 'delivery_status',
            'consignment_id' => $shipment->consignment_id,
            'status' => 'cancelled',
        ];
        $headers = ['X-Webhook-Secret' => 'test-secret'];
        $this->postJson('/webhook/steadfast', $payload, $headers)->assertOk();
        $this->postJson('/webhook/steadfast', $payload, $headers)->assertOk();

        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame('returned', $order->fresh()->status);
        $this->assertSame(1, Transaction::where('reference', 'STEADFAST-RETURN-' . $shipment->id)->count());
        $this->assertDatabaseHas('stock_adjustments', [
            'business_id' => $business->id,
            'product_id' => $product->id,
            'created_by' => $owner->id,
            'type' => 'return',
        ]);
    }

    private function businessAndUser(string $role, ?array $permissions = null): array
    {
        $business = Business::create([
            'name' => 'Test Business',
            'slug' => 'test-business-' . uniqid(),
            'trial_ends_at' => now()->addWeek(),
        ]);
        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Test User',
            'email' => uniqid() . '@example.test',
            'password' => Hash::make('password'),
            'role' => $role,
            'permissions' => $permissions,
            'is_active' => true,
            'is_approved' => true,
        ]);

        return [$business, $user];
    }
}
