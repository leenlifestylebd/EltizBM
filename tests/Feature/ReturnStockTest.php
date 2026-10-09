<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnStockTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Product $product;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $business = Business::create(['name' => 'Return Test', 'slug' => 'return-test', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $business->id, 'name' => 'Owner', 'email' => 'return-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->product = Product::create([
            'business_id' => $business->id, 'name' => 'Return Shirt', 'slug' => 'return-shirt',
            'price' => 100, 'cost_price' => 40, 'stock' => 7, 'low_stock_alert' => 2, 'track_stock' => true,
        ]);
        $this->order = Order::create([
            'business_id' => $business->id, 'created_by' => $this->owner->id,
            'status' => 'delivered', 'payment_status' => 'paid', 'payment_method' => 'cod',
            'customer_name' => 'Buyer', 'subtotal' => 300, 'total' => 300, 'paid_amount' => 300, 'due_amount' => 0,
        ]);
        OrderItem::create([
            'order_id' => $this->order->id, 'product_id' => $this->product->id,
            'product_name' => $this->product->name, 'price' => 100, 'cost_price' => 40,
            'quantity' => 3, 'discount' => 0, 'total' => 300,
        ]);
        $this->actingAs($this->owner);
    }

    public function test_receiving_a_return_restores_stock_through_the_audited_service(): void
    {
        $this->post(route('returns.request', $this->order), ['reason' => 'Defective'])->assertRedirect();
        $this->assertSame('return_requested', $this->order->fresh()->status);
        $this->assertSame('Defective', $this->order->fresh()->return_reason);

        $this->post(route('returns.approve', $this->order), [])->assertRedirect();
        $this->assertSame('return_approved', $this->order->fresh()->status);

        $this->post(route('returns.receive', $this->order), ['condition' => 'good'])->assertRedirect();

        $this->assertSame('return_received', $this->order->fresh()->status);
        $this->assertSame('good', $this->order->fresh()->return_condition);
        $this->assertSame(10, (int) $this->product->fresh()->stock);

        $adjustment = StockAdjustment::where('product_id', $this->product->id)->where('type', 'return')->firstOrFail();
        $this->assertSame(3, (int) $adjustment->quantity);
        $this->assertSame(7, (int) $adjustment->quantity_before);
        $this->assertSame(10, (int) $adjustment->quantity_after);
    }

    public function test_received_return_is_not_restored_a_second_time_when_the_order_is_cancelled(): void
    {
        $this->post(route('returns.request', $this->order), ['reason' => 'Defective'])->assertRedirect();
        $this->post(route('returns.approve', $this->order), [])->assertRedirect();
        $this->post(route('returns.receive', $this->order), ['condition' => 'good'])->assertRedirect();
        $this->assertSame(10, (int) $this->product->fresh()->stock);

        app(OrderService::class)->cancel($this->order->fresh(), 'cancelled');

        $this->assertSame(10, (int) $this->product->fresh()->stock, 'stock must not be restored twice');
    }
}
