<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconcileCustomerLedgerTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $owner;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'Recon Test', 'slug' => 'recon-test', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $this->business->id, 'name' => 'Owner', 'email' => 'recon-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->customer = Customer::create([
            'business_id' => $this->business->id, 'name' => 'Buyer',
            'phone' => '01700000123', 'due_amount' => 0, 'total_purchase' => 0,
        ]);

        $this->order('delivered', 1000, 400);
        $this->order('cancelled', 500, 500);
    }

    private function order(string $status, float $total, float $due): void
    {
        Order::create([
            'business_id' => $this->business->id, 'customer_id' => $this->customer->id,
            'created_by' => $this->owner->id, 'status' => $status, 'payment_status' => $due > 0 ? 'partial' : 'paid',
            'payment_method' => 'cash', 'customer_name' => 'Buyer',
            'subtotal' => $total, 'total' => $total, 'paid_amount' => $total - $due, 'due_amount' => $due,
        ]);
    }

    public function test_dry_run_reports_but_changes_nothing(): void
    {
        $this->artisan('erp:reconcile-customer-ledger')
            ->expectsOutputToContain('DRY RUN')
            ->assertExitCode(0);

        $this->assertSame(0.0, (float) $this->customer->fresh()->due_amount);
        $this->assertSame(0.0, (float) $this->customer->fresh()->total_purchase);
    }

    public function test_apply_corrects_the_counters_and_is_idempotent(): void
    {
        $this->artisan('erp:reconcile-customer-ledger', ['--apply' => true])->assertExitCode(0);

        // due = non-terminal orders only (1000-... order with due 400); cancelled excluded.
        $this->assertSame(400.0, (float) $this->customer->fresh()->due_amount);
        // total_purchase counts all retained orders.
        $this->assertSame(1500.0, (float) $this->customer->fresh()->total_purchase);

        // Nothing is deleted — both orders remain.
        $this->assertSame(2, Order::where('customer_id', $this->customer->id)->count());

        // A second run finds nothing left to do.
        $this->artisan('erp:reconcile-customer-ledger')
            ->expectsOutputToContain('No customer counter discrepancies')
            ->assertExitCode(0);
    }
}
