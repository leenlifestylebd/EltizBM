<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CourierSetting;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CodStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Order $order;
    private Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();
        $business = Business::create(['name' => 'COD Status', 'slug' => 'cod-status', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $business->id, 'name' => 'Owner', 'email' => 'cod-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        CourierSetting::create([
            'business_id' => $business->id, 'provider' => 'steadfast',
            'api_key' => 'k', 'api_secret' => 's', 'is_active' => true,
        ]);
        $this->order = Order::create([
            'business_id' => $business->id, 'created_by' => $this->owner->id,
            'status' => 'shipped', 'payment_status' => 'unpaid', 'payment_method' => 'cod',
            'customer_name' => 'Buyer', 'subtotal' => 1000, 'total' => 1000, 'paid_amount' => 0, 'due_amount' => 1000,
        ]);
        $this->shipment = Shipment::create([
            'business_id' => $business->id, 'order_id' => $this->order->id, 'courier_name' => 'Steadfast',
            'tracking_id' => 'T-1', 'consignment_id' => 'C-1', 'status' => 'in_transit', 'cod_amount' => 0,
        ]);
        $this->actingAs($this->owner);
    }

    public function test_status_check_books_only_the_collected_cod_and_is_idempotent(): void
    {
        Http::fake(['portal.packzy.com/*' => Http::response(['delivery_status' => 'delivered', 'cod_amount' => 400], 200)]);

        $this->get(route('steadfast.check-status', ['type' => 'tracking', 'value' => 'T-1']))->assertOk();

        $this->assertSame(400.0, (float) $this->order->fresh()->paid_amount);
        $this->assertSame(600.0, (float) $this->order->fresh()->due_amount);
        $this->assertSame('partial', $this->order->fresh()->payment_status);
        $this->assertSame(400.0, (float) Transaction::where('order_id', $this->order->id)->sum('amount'));

        // A repeated status check must not book the collection again.
        $this->get(route('steadfast.check-status', ['type' => 'tracking', 'value' => 'T-1']))->assertOk();
        $this->assertSame(400.0, (float) $this->order->fresh()->paid_amount);
        $this->assertSame(1, Transaction::where('order_id', $this->order->id)->count());
    }
}
