<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'Audit Test', 'slug' => 'audit-test', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $this->business->id, 'name' => 'Owner', 'email' => 'audit-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
    }

    public function test_the_audit_reports_discrepancies_and_writes_nothing(): void
    {
        $customer = Customer::create(['business_id' => $this->business->id, 'name' => 'Buyer', 'due_amount' => 0, 'total_purchase' => 0]);
        $order = Order::create([
            'business_id' => $this->business->id, 'customer_id' => $customer->id, 'created_by' => $this->owner->id,
            'status' => 'delivered', 'payment_status' => 'partial', 'payment_method' => 'cash', 'customer_name' => 'Buyer',
            'subtotal' => 1000, 'total' => 1000, 'paid_amount' => 0, 'due_amount' => 555,
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_name' => 'X', 'price' => 1000, 'quantity' => 1, 'total' => 1000]);
        Transaction::create([
            'business_id' => $this->business->id, 'created_by' => $this->owner->id, 'order_id' => 999999,
            'type' => 'income', 'title' => 'Orphan', 'amount' => 10, 'payment_method' => 'cash', 'transaction_date' => today(),
        ]);

        $ordersBefore = Order::count();
        $txnsBefore = Transaction::count();
        $customersBefore = Customer::count();

        $this->artisan('erp:financial-audit')
            ->expectsOutputToContain('Read-only report')
            ->assertExitCode(0);

        // Nothing was written, updated or deleted.
        $this->assertSame($ordersBefore, Order::count());
        $this->assertSame($txnsBefore, Transaction::count());
        $this->assertSame($customersBefore, Customer::count());
        $this->assertSame(555.0, (float) $order->fresh()->due_amount);
        $this->assertSame(0.0, (float) $customer->fresh()->due_amount);
    }

    public function test_csv_export_writes_the_findings_file(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eltiz-fin-audit-' . uniqid() . '.csv';

        $this->artisan('erp:financial-audit', ['--csv' => $path])->assertExitCode(0);

        $this->assertFileExists($path);
        $this->assertStringContainsString('check', (string) file_get_contents($path));
    }
}
