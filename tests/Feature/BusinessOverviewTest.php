<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\Order;
use App\Models\ProductionEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_overview_totals_are_correct(): void
    {
        $business = Business::create(['name' => 'Overview', 'slug' => 'overview', 'trial_ends_at' => now()->addWeek()]);
        $user = User::create([
            'business_id' => $business->id, 'name' => 'Owner', 'email' => 'ov@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $employee = Employee::create([
            'business_id' => $business->id, 'name' => 'Worker', 'salary' => 1000,
            'joining_date' => now()->startOfYear()->toDateString(), 'type' => 'salary', 'status' => 'active',
        ]);

        Order::create([
            'business_id' => $business->id, 'created_by' => $user->id, 'status' => 'delivered',
            'payment_status' => 'paid', 'payment_method' => 'cash', 'customer_name' => 'Buyer',
            'subtotal' => 500, 'total' => 500, 'paid_amount' => 500, 'due_amount' => 0,
        ]);
        Transaction::create([
            'business_id' => $business->id, 'created_by' => $user->id, 'type' => 'expense',
            'title' => 'Supplies', 'amount' => 100, 'payment_method' => 'cash', 'reference' => null, 'transaction_date' => today(),
        ]);
        ProductionEntry::create([
            'business_id' => $business->id, 'employee_id' => $employee->id,
            'entry_date' => today(), 'quantity' => 3, 'amount' => 30,
        ]);

        $data = app(ReportQueryService::class)->getBusinessOverview($business->id, (int) now()->year);
        $month = $data['months'][now()->month - 1];

        $this->assertSame(500.0, (float) $month['revenue']);
        // expense = manual 100 + production 30 + base salary 1000
        $this->assertSame(1130.0, (float) $month['expense']);
        $this->assertSame(0.0, (float) $month['income']);
        $this->assertSame(-630.0, (float) $month['profit']);
    }
}
