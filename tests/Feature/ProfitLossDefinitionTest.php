<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitLossDefinitionTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $user;
    private string $from;
    private string $to;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'PL Test', 'slug' => 'pl-test', 'trial_ends_at' => now()->addWeek()]);
        $this->user = User::create([
            'business_id' => $this->business->id, 'name' => 'Owner', 'email' => 'pl-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->from = now()->startOfMonth()->toDateString();
        $this->to   = now()->endOfMonth()->toDateString();
    }

    private function expense(float $amount, ?string $reference, string $title = 'Expense'): void
    {
        Transaction::create([
            'business_id' => $this->business->id, 'created_by' => $this->user->id,
            'type' => 'expense', 'title' => $title,
            'amount' => $amount, 'payment_method' => 'cash', 'reference' => $reference,
            'transaction_date' => today(),
        ]);
    }

    public function test_labour_and_inventory_costs_are_counted_once_and_manual_expenses_are_kept(): void
    {
        $user = $this->user;
        $employee = Employee::create([
            'business_id' => $this->business->id, 'name' => 'Worker', 'salary' => 0,
            'type' => 'production', 'status' => 'active',
        ]);

        // One delivered sale: 100 revenue, 40 cost.
        $order = Order::create([
            'business_id' => $this->business->id, 'created_by' => $user->id, 'status' => 'delivered',
            'payment_status' => 'paid', 'payment_method' => 'cash', 'customer_name' => 'Buyer',
            'subtotal' => 100, 'total' => 100, 'paid_amount' => 100, 'due_amount' => 0,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_name' => 'Shirt', 'price' => 100,
            'cost_price' => 40, 'quantity' => 1, 'discount' => 0, 'total' => 100,
        ]);

        // Piece-rate labour earned (accrual).
        ProductionEntry::create([
            'business_id' => $this->business->id, 'employee_id' => $employee->id,
            'entry_date' => today(), 'quantity' => 3, 'amount' => 30,
        ]);

        // The wage payment (EMPPAY) and supplier payment (PO-PAY) must NOT be counted
        // again as expenses; a plain manual expense (null reference) MUST be counted.
        $this->expense(30, 'EMPPAY-' . $employee->id, 'Advance payment');
        $this->expense(500, 'PO-PAY-1', 'Purchase payment');
        $this->expense(20, null, 'Office supplies');

        // A transfer is neither income nor expense.
        $this->expense(50, 'TRANSFER-1', 'Transfer out');
        Transaction::create([
            'business_id' => $this->business->id, 'created_by' => $this->user->id,
            'type' => 'income', 'title' => 'Transfer in',
            'amount' => 50, 'payment_method' => 'bKash', 'reference' => 'TRANSFER-1', 'transaction_date' => today(),
        ]);

        $data = app(ReportQueryService::class)->getProfitLoss($this->business->id, $this->from, $this->to);

        $this->assertSame(100.0, (float) $data['revenue']);
        $this->assertSame(40.0, (float) $data['cogs']);
        $this->assertSame(20.0, (float) $data['expenses'], 'only real operating expenses count');
        $this->assertSame(30.0, (float) $data['productionWages'], 'labour counted once (production + base salary)');
        $this->assertSame(0.0, (float) $data['otherIncome']);
        $this->assertSame(10.0, (float) $data['netProfit'], '100 - 40 + 0 - 20 - 30');
    }

    public function test_operating_expense_total_helper_matches_the_pl_expense_definition(): void
    {
        $this->expense(20, null, 'Office supplies');
        $this->expense(30, 'EMPPAY-1');
        $this->expense(500, 'PO-PAY-1');
        $this->expense(50, 'TRANSFER-1');

        $total = app(ReportQueryService::class)->getOperatingExpenseTotal($this->business->id, $this->from, $this->to);

        $this->assertSame(20.0, (float) $total);
    }

    public function test_an_order_late_on_the_final_day_of_the_period_is_included(): void
    {
        $order = new Order([
            'business_id' => $this->business->id, 'created_by' => $this->user->id, 'status' => 'delivered',
            'payment_status' => 'paid', 'payment_method' => 'cash', 'customer_name' => 'Buyer',
            'subtotal' => 100, 'total' => 100, 'paid_amount' => 100, 'due_amount' => 0,
        ]);
        $order->created_at = now()->endOfMonth()->setTime(23, 30, 0);
        $order->save();

        $data = app(ReportQueryService::class)->getProfitLoss($this->business->id, $this->from, $this->to);

        $this->assertSame(100.0, (float) $data['revenue'], 'the final day must not be truncated');
    }
}
