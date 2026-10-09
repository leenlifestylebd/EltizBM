<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseCostTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $business = Business::create(['name' => 'PO Cost', 'slug' => 'po-cost', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $business->id, 'name' => 'Owner', 'email' => 'po-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->product = Product::create([
            'business_id' => $business->id, 'name' => 'Fabric', 'slug' => 'po-fabric',
            'price' => 100, 'cost_price' => 10, 'stock' => 0, 'low_stock_alert' => 2, 'track_stock' => true,
        ]);
        $this->actingAs($this->owner);
    }

    public function test_a_draft_purchase_order_does_not_revalue_inventory_until_it_is_received(): void
    {
        $this->post(route('inventory.purchase-orders.store'), [
            'vendor_id' => null, 'status' => 'draft',
            'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'cost_price' => 25]],
        ])->assertRedirect();

        $this->assertSame(10.0, (float) $this->product->fresh()->cost_price, 'draft PO must not change cost');
        $this->assertSame(0, (int) $this->product->fresh()->stock);

        $po = PurchaseOrder::latest('id')->firstOrFail();

        $this->post(route('inventory.purchase-orders.store'), [
            'action' => 'mark_received', 'po_id' => $po->id,
        ])->assertRedirect();

        $this->assertSame(25.0, (float) $this->product->fresh()->cost_price);
        $this->assertSame(5, (int) $this->product->fresh()->stock);
    }
}
