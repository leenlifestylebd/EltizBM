<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\Business;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $business = Business::create(['name' => 'Gateway Test', 'slug' => 'gateway-test', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $business->id, 'name' => 'Owner', 'email' => 'gateway-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->order = Order::create([
            'business_id' => $business->id, 'created_by' => $this->owner->id,
            'status' => 'pending', 'payment_status' => 'unpaid', 'payment_method' => 'cash',
            'customer_name' => 'Buyer', 'subtotal' => 1000, 'discount' => 0, 'shipping_charge' => 0,
            'total' => 1000, 'paid_amount' => 0, 'due_amount' => 1000,
        ]);
        $this->actingAs($this->owner);
    }

    public function test_gateway_payment_records_a_valid_income_transaction_and_marks_the_order_paid(): void
    {
        $recorded = app(PaymentController::class)->markOrderPaid($this->order, 'bkash', 'PAY-1', 'TRX-1', 1000.0);

        $this->assertTrue($recorded);
        $this->assertSame('paid', $this->order->fresh()->payment_status);
        $this->assertSame(1000.0, (float) $this->order->fresh()->paid_amount);
        $this->assertSame(0.0, (float) $this->order->fresh()->due_amount);

        $txn = Transaction::where('order_id', $this->order->id)->where('type', 'income')->firstOrFail();
        $this->assertSame(1000.0, (float) $txn->amount);
        $this->assertSame('bkash', $txn->payment_method);
        $this->assertNotNull($txn->title);
        $this->assertNotNull($txn->transaction_date);
        $this->assertSame($this->owner->id, $txn->created_by);
    }

    public function test_replayed_gateway_callback_does_not_double_credit_the_order(): void
    {
        $controller = app(PaymentController::class);
        $controller->markOrderPaid($this->order, 'bkash', 'PAY-1', 'TRX-1', 1000.0);

        $second = $controller->markOrderPaid($this->order->fresh(), 'bkash', 'PAY-1', 'TRX-1', 1000.0);

        $this->assertFalse($second);
        $this->assertSame(1000.0, (float) $this->order->fresh()->paid_amount);
        $this->assertSame(1, Transaction::where('order_id', $this->order->id)->where('type', 'income')->count());
    }

    public function test_partial_gateway_payment_leaves_the_remaining_due(): void
    {
        app(PaymentController::class)->markOrderPaid($this->order, 'nagad', 'PAY-2', 'TRX-2', 400.0);

        $this->assertSame('partial', $this->order->fresh()->payment_status);
        $this->assertSame(400.0, (float) $this->order->fresh()->paid_amount);
        $this->assertSame(600.0, (float) $this->order->fresh()->due_amount);
    }
}
