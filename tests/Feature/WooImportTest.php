<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WoocommerceSetting;
use App\Services\WooCommerceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WooImportTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $owner;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'Woo Test', 'slug' => 'woo-test', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $this->business->id, 'name' => 'Owner', 'email' => 'woo-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->product = Product::create([
            'business_id' => $this->business->id, 'name' => 'Shirt', 'slug' => 'woo-shirt',
            'price' => 100, 'cost_price' => 40, 'stock' => 10, 'low_stock_alert' => 2,
            'track_stock' => true, 'woo_product_id' => 555,
        ]);
    }

    private function service(): WooCommerceService
    {
        $setting = WoocommerceSetting::create([
            'business_id' => $this->business->id, 'store_url' => 'https://shop.example.test',
            'consumer_key' => 'ck', 'consumer_secret' => 'cs', 'is_active' => true,
        ]);
        return new WooCommerceService($setting);
    }

    private function woOrder(string $status): array
    {
        return [
            'id' => 9001, 'status' => $status, 'total' => '300', 'shipping_total' => '0', 'discount_total' => '0',
            'payment_method' => 'bpay', 'customer_id' => 77,
            'billing' => ['first_name' => 'A', 'last_name' => 'B', 'phone' => '01711111111', 'email' => 'a@b.test', 'address_1' => 'X', 'city' => 'Dhaka', 'state' => 'Dhaka'],
            'shipping' => ['first_name' => 'A', 'last_name' => 'B', 'address_1' => 'X', 'address_2' => '', 'city' => 'Dhaka', 'state' => 'Dhaka'],
            'line_items' => [[
                'product_id' => 555, 'name' => 'Shirt', 'sku' => 'S1', 'price' => '100',
                'quantity' => 3, 'subtotal' => '300', 'total' => '300', 'total_tax' => '15',
            ]],
        ];
    }

    public function test_a_paid_online_order_books_income_deducts_stock_and_updates_the_customer(): void
    {
        $order = $this->service()->importWooOrder($this->woOrder('completed'), $this->business->id);

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(300.0, (float) $order->paid_amount);
        $this->assertSame(0.0, (float) $order->due_amount);
        $this->assertSame(7, (int) $this->product->fresh()->stock);

        $this->assertSame(300.0, (float) Transaction::where('order_id', $order->id)->where('type', 'income')->sum('amount'));

        $customer = Customer::findOrFail($order->customer_id);
        $this->assertSame(300.0, (float) $customer->total_purchase);

        // Tax must not be stored as the line discount.
        $this->assertSame(0.0, (float) $order->items()->first()->discount);
    }

    public function test_an_unpaid_online_order_tracks_the_customer_due_without_booking_income(): void
    {
        $order = $this->service()->importWooOrder($this->woOrder('pending'), $this->business->id);

        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(300.0, (float) $order->due_amount);
        $this->assertSame(0, Transaction::where('order_id', $order->id)->count());

        $customer = Customer::findOrFail($order->customer_id);
        $this->assertSame(300.0, (float) $customer->due_amount);
    }
}
