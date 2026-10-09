<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDeletionTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $owner;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'Delete Test', 'slug' => 'delete-test', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $this->business->id, 'name' => 'Owner', 'email' => 'delete-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->product = Product::create([
            'business_id' => $this->business->id, 'name' => 'Shirt', 'slug' => 'del-shirt',
            'price' => 100, 'stock' => 10, 'low_stock_alert' => 2, 'track_stock' => true,
        ]);
        $this->actingAs($this->owner);
    }

    private function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'business_id' => $this->business->id, 'created_by' => $this->owner->id,
            'status' => 'confirmed', 'payment_status' => 'unpaid', 'payment_method' => 'cash',
            'customer_name' => 'Buyer', 'subtotal' => 200, 'total' => 200,
            'paid_amount' => 0, 'due_amount' => 200,
        ], $overrides));
    }

    public function test_order_with_booked_money_cannot_be_deleted_and_its_transactions_survive(): void
    {
        $order = $this->makeOrder(['payment_status' => 'partial', 'paid_amount' => 100, 'due_amount' => 100]);
        $txn = Transaction::create([
            'business_id' => $this->business->id, 'created_by' => $this->owner->id, 'order_id' => $order->id,
            'type' => 'income', 'title' => 'Payment for order', 'amount' => 100,
            'payment_method' => 'cash', 'transaction_date' => today(),
        ]);

        $this->delete(route('orders.destroy', $order))->assertRedirect()->assertSessionHas('error');

        $this->assertNotSoftDeleted($order);
        $this->assertDatabaseHas('transactions', ['id' => $txn->id, 'deleted_at' => null]);
    }

    public function test_deleting_a_delivered_order_does_not_put_stock_back_on_the_shelf(): void
    {
        $order = $this->makeOrder(['status' => 'delivered', 'payment_status' => 'paid', 'paid_amount' => 200, 'due_amount' => 0]);
        // The order is unpaid in the books only via the transaction check; give it none.
        $this->assertSame(0, Transaction::where('order_id', $order->id)->count());

        $this->delete(route('orders.destroy', $order))->assertRedirect();

        $this->assertSoftDeleted($order);
        $this->assertSame(10, (int) $this->product->fresh()->stock);
    }

    public function test_deleting_an_unbooked_order_rolls_back_its_customer_ledger_entries(): void
    {
        $customer = Customer::create([
            'business_id' => $this->business->id, 'name' => 'Buyer', 'phone' => '01700000000',
            'total_purchase' => 200, 'due_amount' => 50,
        ]);
        $order = $this->makeOrder(['customer_id' => $customer->id, 'total' => 200, 'due_amount' => 50, 'paid_amount' => 150]);

        $this->delete(route('orders.destroy', $order))->assertRedirect();

        $customer->refresh();
        $this->assertSame(0.0, (float) $customer->due_amount);
        $this->assertSame(0.0, (float) $customer->total_purchase);
    }
}
