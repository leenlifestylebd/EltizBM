<?php

namespace Tests\Feature;

use App\Models\{Business, Order, OrderItem, Product, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Renders the pages most affected by structural changes (routes added or
 * removed, views edited in bulk). A deleted view or a tab bar still pointing at
 * a removed route is otherwise only discovered by clicking the sidebar.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::preventStrayRequests();

        $this->business = Business::create(['name' => 'Smoke', 'slug' => uniqid('smoke-'), 'trial_ends_at' => now()->addWeek()]);
        $owner = User::create([
            'business_id' => $this->business->id, 'name' => 'Owner',
            'email' => uniqid().'@example.test', 'password' => 'password',
            'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->actingAs($owner);

        $product = Product::create(['business_id' => $this->business->id, 'name' => 'Smoke Pants', 'slug' => 'smoke-pants', 'price' => 100, 'stock' => 5, 'track_stock' => true]);
        $this->order = Order::create([
            'business_id' => $this->business->id, 'created_by' => $owner->id, 'order_number' => 'ORD-SMOKE',
            'source' => 'manual', 'status' => 'pending', 'payment_status' => 'unpaid',
            'payment_method' => 'cod', 'customer_name' => 'Customer',
            'subtotal' => 100, 'total' => 100, 'paid_amount' => 0, 'due_amount' => 100,
        ]);
        OrderItem::create(['order_id' => $this->order->id, 'product_id' => $product->id, 'product_name' => $product->name, 'price' => 100, 'quantity' => 1, 'total' => 100]);
    }

    public function test_core_pages_render(): void
    {
        foreach ([
            'dashboard',
            'orders.index', 'orders.create', 'orders.show', 'orders.edit', 'orders.invoice', 'orders.print',
            'pos.index',
        ] as $name) {
            $this->get(route($name, ['order' => $this->order->id]))->assertOk();
        }
    }

    public function test_inventory_pages_render(): void
    {
        foreach ([
            'inventory.overview', 'inventory.stock-levels', 'inventory.adjustments',
            'inventory.purchase-orders', 'inventory.cutting-list', 'inventory.barcode',
        ] as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    /** Every settings tab bar is rendered on each page, so a removed route breaks all of them. */
    public function test_settings_pages_render(): void
    {
        // settings.subscription is excluded: no route has ever been defined for it,
        // so the view is unreachable dead code unrelated to this change.
        foreach ([
            'settings.general', 'settings.business', 'settings.user-management',
            'settings.print-templates', 'settings.account-categories', 'settings.theme',
            'settings.statuses', 'settings.tags', 'settings.payment-history',
        ] as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    public function test_inventory_has_no_multi_warehouse_routes(): void
    {
        foreach (['inventory.transfers', 'inventory.warehouse-stock', 'settings.warehouses'] as $name) {
            $this->assertFalse(Route::has($name), "Route [{$name}] should no longer exist.");
        }
    }

    public function test_order_pages_do_not_offer_a_warehouse_choice(): void
    {
        $this->get(route('orders.create'))->assertOk()
            ->assertDontSee('Select warehouse / outlet')
            ->assertDontSee('name="warehouse_id"', false);

        $this->get(route('orders.edit', $this->order))->assertOk()
            ->assertDontSee('name="warehouse_id"', false);

        $this->get(route('orders.show', $this->order))->assertOk()->assertSee('Main Warehouse');
    }
}