<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SteadfastWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Order $order;
    private Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.steadfast.webhook_secret' => 'test-secret']);
        $business = Business::create(['name' => 'Webhook Test', 'slug' => 'webhook-test', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $business->id, 'name' => 'Owner', 'email' => 'webhook-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->order = Order::create([
            'business_id' => $business->id, 'created_by' => $this->owner->id,
            'status' => 'shipped', 'payment_status' => 'unpaid', 'payment_method' => 'cod',
            'customer_name' => 'Buyer', 'subtotal' => 1000, 'total' => 1000, 'paid_amount' => 0, 'due_amount' => 1000,
        ]);
        $this->shipment = Shipment::create([
            'business_id' => $business->id, 'order_id' => $this->order->id, 'courier_name' => 'Steadfast',
            'consignment_id' => 'C-PART', 'status' => 'in_transit',
        ]);
    }

    public function test_secret_in_the_query_string_is_rejected(): void
    {
        $this->postJson('/webhook/steadfast?secret=test-secret', [
            'notification_type' => 'delivery_status', 'consignment_id' => 'C-PART', 'status' => 'delivered',
        ])->assertStatus(401);
    }

    public function test_replayed_partial_cod_delivery_does_not_credit_the_order_twice(): void
    {
        $payload = [
            'notification_type' => 'delivery_status', 'consignment_id' => 'C-PART',
            'status' => 'delivered', 'cod_amount' => 400,
        ];
        $headers = ['X-Webhook-Secret' => 'test-secret'];

        $this->postJson('/webhook/steadfast', $payload, $headers)->assertOk();
        $this->postJson('/webhook/steadfast', $payload, $headers)->assertOk();

        $order = $this->order->fresh();
        $this->assertSame(400.0, (float) $order->paid_amount);
        $this->assertSame(600.0, (float) $order->due_amount);
        $this->assertSame('partial', $order->payment_status);
        $this->assertSame(1, Transaction::where('order_id', $this->order->id)->where('reference', 'STEADFAST-COD-' . $this->shipment->id)->count());
    }

    public function test_unknown_consignment_is_ignored_without_touching_other_orders(): void
    {
        $this->postJson('/webhook/steadfast', [
            'notification_type' => 'delivery_status', 'consignment_id' => 'DOES-NOT-EXIST', 'status' => 'delivered',
        ], ['X-Webhook-Secret' => 'test-secret'])->assertOk();

        $this->assertSame(0.0, (float) $this->order->fresh()->paid_amount);
        $this->assertSame(0, Transaction::where('order_id', $this->order->id)->count());
    }
}
