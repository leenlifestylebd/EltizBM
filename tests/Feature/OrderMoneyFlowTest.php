<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderMoneyFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $business = Business::create(['name' => 'Money Flow', 'slug' => 'money-flow', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $business->id, 'name' => 'Owner', 'email' => 'money-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->actingAs($this->owner);
    }

    private function customer(float $due): Customer
    {
        return Customer::create([
            'business_id' => $this->owner->business_id, 'name' => 'Buyer',
            'phone' => '0170' . random_int(1000000, 9999999), 'total_purchase' => 1000, 'due_amount' => $due,
        ]);
    }

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'business_id' => $this->owner->business_id, 'created_by' => $this->owner->id,
            'status' => 'pending', 'payment_status' => 'unpaid', 'payment_method' => 'cod',
            'customer_name' => 'Buyer', 'subtotal' => 1000, 'total' => 1000,
            'paid_amount' => 0, 'due_amount' => 1000,
        ], $overrides));
    }

    public function test_collecting_more_than_the_due_does_not_over_credit_or_over_decrement(): void
    {
        $customer = $this->customer(100);
        $order = $this->order(['total' => 100, 'due_amount' => 100, 'customer_id' => $customer->id]);

        app(OrderService::class)->collectPayment($order, 500.0);

        $this->assertSame(100.0, (float) $order->fresh()->paid_amount);
        $this->assertSame(0.0, (float) $order->fresh()->due_amount);
        $this->assertSame(100.0, (float) Transaction::where('order_id', $order->id)->where('type', 'income')->sum('amount'));
        $this->assertSame(0.0, (float) $customer->fresh()->due_amount, 'customer due must not go negative');
    }

    public function test_cod_completion_books_only_the_outstanding_amount_and_clears_customer_due(): void
    {
        $customer = $this->customer(600);
        $order = $this->order(['total' => 1000, 'paid_amount' => 400, 'due_amount' => 600, 'payment_status' => 'partial', 'customer_id' => $customer->id]);

        app(OrderService::class)->completeCod($order);

        $this->assertSame(1000.0, (float) $order->fresh()->paid_amount);
        $this->assertSame(0.0, (float) $order->fresh()->due_amount);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(600.0, (float) Transaction::where('order_id', $order->id)->where('type', 'income')->sum('amount'));
        $this->assertSame(0.0, (float) $customer->fresh()->due_amount);
    }

    public function test_refund_closes_the_order_and_clears_the_customer_due(): void
    {
        $customer = $this->customer(400);
        $order = $this->order([
            'status' => 'return_received', 'payment_status' => 'partial',
            'paid_amount' => 600, 'due_amount' => 400, 'customer_id' => $customer->id,
        ]);

        $this->post(route('returns.refund', $order), ['refund_amount' => 300, 'refund_method' => 'cash'])->assertRedirect();

        $order->refresh();
        $this->assertSame('refunded', $order->status);
        $this->assertSame(300.0, (float) $order->paid_amount);
        $this->assertSame(0.0, (float) $order->due_amount);
        $this->assertSame(0.0, (float) $customer->fresh()->due_amount);

        $refund = Transaction::where('order_id', $order->id)->where('type', 'expense')->firstOrFail();
        $this->assertSame(300.0, (float) $refund->amount);
    }
}
