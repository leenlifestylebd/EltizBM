<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $business = Business::create(['name' => 'POS Test', 'slug' => 'pos-test', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $business->id, 'name' => 'Owner', 'email' => 'pos-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->product = Product::create([
            'business_id' => $business->id, 'name' => 'Panjabi', 'slug' => 'pos-panjabi',
            'price' => 100, 'cost_price' => 40, 'stock' => 10, 'low_stock_alert' => 2, 'track_stock' => true,
        ]);
        $this->actingAs($this->owner);
    }

    public function test_client_supplied_prices_and_totals_are_ignored(): void
    {
        $this->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $this->product->id, 'product_name' => 'Tampered', 'price' => 0, 'quantity' => 2, 'total' => 0]],
            'total' => 0,
            'payment_method' => 'cash',
        ])->assertOk()->assertJson(['success' => true]);

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame(200.0, (float) $order->total);
        $this->assertSame(200.0, (float) $order->paid_amount);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(8, (int) $this->product->fresh()->stock);

        $item = OrderItem::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(100.0, (float) $item->price);
        $this->assertSame(200.0, (float) $item->total);
        $this->assertSame(200.0, (float) Transaction::where('order_id', $order->id)->where('type', 'income')->sum('amount'));
    }

    public function test_a_foreign_customer_id_is_never_mutated(): void
    {
        $other = Business::create(['name' => 'Other', 'slug' => 'pos-other', 'trial_ends_at' => now()->addWeek()]);
        $foreign = Customer::create(['business_id' => $other->id, 'name' => 'Foreign', 'total_purchase' => 0, 'due_amount' => 0]);

        $this->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'customer_id' => $foreign->id,
        ])->assertOk();

        $this->assertSame(0.0, (float) $foreign->fresh()->total_purchase);
        $this->assertSame(0.0, (float) $foreign->fresh()->due_amount);
    }

    public function test_a_fully_paid_pos_sale_still_updates_the_local_customer_ledger(): void
    {
        $customer = Customer::create([
            'business_id' => $this->owner->business_id, 'name' => 'Local',
            'phone' => '01709999999', 'total_purchase' => 0, 'due_amount' => 0,
        ]);

        $this->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $this->product->id, 'quantity' => 3]],
            'payment_method' => 'cash',
            'customer_id' => $customer->id,
        ])->assertOk();

        $this->assertSame(300.0, (float) $customer->fresh()->total_purchase);
        $this->assertSame(0.0, (float) $customer->fresh()->due_amount);
    }
}
