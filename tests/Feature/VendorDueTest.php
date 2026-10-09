<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorDueTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        $business = Business::create(['name' => 'Vendor Due', 'slug' => 'vendor-due', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $business->id, 'name' => 'Owner', 'email' => 'vendor-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->vendor = Vendor::create(['business_id' => $business->id, 'name' => 'Supplier Co', 'due_amount' => 0]);
        PurchaseOrder::create([
            'business_id' => $business->id, 'vendor_id' => $this->vendor->id, 'created_by' => $this->owner->id,
            'po_number' => 'PO-DUE-1', 'status' => 'received', 'payment_status' => 'partial',
            'total' => 500, 'paid_amount' => 200,
        ]);
        $this->actingAs($this->owner);
    }

    public function test_vendor_page_shows_due_derived_from_purchase_orders(): void
    {
        $this->get(route('people.vendors.show', $this->vendor))
            ->assertOk()
            ->assertSee('300');
    }

    public function test_supplier_due_report_lists_the_outstanding_balance(): void
    {
        $this->get(route('reports.supplier-due'))
            ->assertOk()
            ->assertSee('Supplier Co')
            ->assertSee('300.00');
    }
}
