<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\Order;
use App\Models\ProductionEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportQueryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every report must classify transactions the same way: internal transfers are not
 * income/expense, worker wage payments are reported once (as cash, separately from
 * operating categories), and genuine rows with a NULL/empty reference are never dropped.
 */
class ReportExpenseConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $owner;
    private string $from;
    private string $to;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'Report Test', 'slug' => 'report-test', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $this->business->id, 'name' => 'Owner', 'email' => 'report-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->from = now()->startOfMonth()->toDateString();
        $this->to   = now()->endOfMonth()->toDateString();
    }

    private function svc(): ReportQueryService
    {
        return app(ReportQueryService::class);
    }

    private function expense(float $amount, ?string $reference, ?string $date = null, ?int $bizId = null): Transaction
    {
        return Transaction::create([
            'business_id'      => $bizId ?? $this->business->id,
            'created_by'       => $this->owner->id,
            'type'             => 'expense',
            'title'            => 'Expense',
            'amount'           => $amount,
            'payment_method'   => 'cash',
            'reference'        => $reference,
            'transaction_date' => $date ?? now()->toDateString(),
        ]);
    }

    private function income(float $amount, string $reference, ?int $bizId = null): Transaction
    {
        return Transaction::create([
            'business_id'      => $bizId ?? $this->business->id,
            'created_by'       => $this->owner->id,
            'type'             => 'income',
            'title'            => 'Income',
            'amount'           => $amount,
            'payment_method'   => 'cash',
            'reference'        => $reference,
            'transaction_date' => now()->toDateString(),
        ]);
    }

    public function test_operating_expense_keeps_null_empty_and_unrelated_references(): void
    {
        $this->expense(100, 'INV-9');   // an ordinary reference
        $this->expense(50, null);       // NULL reference — the old filter wrongly dropped this
        $this->expense(25, '');         // empty reference

        $pl = $this->svc()->getProfitLoss($this->business->id, $this->from, $this->to);

        $this->assertSame(175.0, (float) $pl['expenses']);
    }

    public function test_internal_transfer_legs_are_excluded_everywhere(): void
    {
        $this->expense(100, 'INV-9');
        $this->expense(999, 'TRANSFER-1754300000');       // transfer expense leg
        $this->income(999, 'TRANSFER-1754300000');        // transfer income leg

        $svc = $this->svc();
        $pl  = $svc->getProfitLoss($this->business->id, $this->from, $this->to);
        $cat = $svc->getExpenseCategory($this->business->id, $this->from, $this->to);
        $inc = $svc->getIncomeSource($this->business->id, $this->from, $this->to);

        $this->assertSame(100.0, (float) $pl['expenses']);
        $this->assertSame(0.0, (float) $pl['otherIncome']);
        $this->assertSame(100.0, (float) $cat['totalExpense']);
        $this->assertSame(0.0, (float) $inc['totalIncome']);
    }

    public function test_another_business_transactions_are_excluded(): void
    {
        $other = Business::create(['name' => 'Other', 'slug' => 'other-report-biz', 'trial_ends_at' => now()->addWeek()]);
        $this->expense(500, null, null, $other->id);
        $this->expense(100, null);

        $this->assertSame(100.0, (float) $this->svc()->getProfitLoss($this->business->id, $this->from, $this->to)['expenses']);
    }

    public function test_date_range_includes_only_the_selected_period(): void
    {
        $mid = now()->startOfMonth()->addDays(5)->toDateString();

        $this->expense(100, null, $this->from);
        $this->expense(200, null, $this->to);
        $this->expense(400, null, now()->subMonthNoOverflow()->startOfMonth()->subDay()->toDateString()); // before range
        $this->expense(7, null, $mid);

        $svc = $this->svc();
        $this->assertSame(307.0, (float) $svc->getProfitLoss($this->business->id, $this->from, $this->to)['expenses']);
        $this->assertSame(7.0, (float) $svc->getProfitLoss($this->business->id, $mid, $mid)['expenses']);
    }

    public function test_revenue_includes_an_order_late_on_the_final_day(): void
    {
        $order = Order::create([
            'business_id' => $this->business->id, 'created_by' => $this->owner->id, 'status' => 'delivered',
            'payment_status' => 'paid', 'payment_method' => 'cash', 'customer_name' => 'Buyer',
            'subtotal' => 100, 'total' => 100, 'paid_amount' => 100, 'due_amount' => 0,
        ]);
        $order->created_at = Carbon::parse($this->to)->setTime(23, 30, 0);
        $order->save();

        $this->assertSame(100.0, (float) $this->svc()->getProfitLoss($this->business->id, $this->from, $this->to)['revenue']);
    }

    public function test_expense_category_ties_to_pnl_and_to_its_own_rows(): void
    {
        $this->expense(120, null);
        $this->expense(80, 'INV-1');
        $this->expense(999, 'TRANSFER-1');

        // A worker wage payment (EMPPAY) — a real cash cost reported on its own line.
        Transaction::create([
            'business_id' => $this->business->id, 'created_by' => $this->owner->id, 'type' => 'expense',
            'title' => 'Salary payment', 'amount' => 300, 'payment_method' => 'cash',
            'reference' => 'EMPPAY-1', 'transaction_date' => now()->toDateString(),
        ]);

        $svc = $this->svc();
        $pl  = $svc->getProfitLoss($this->business->id, $this->from, $this->to);
        $cat = $svc->getExpenseCategory($this->business->id, $this->from, $this->to);

        // The category total must equal the sum of its displayed rows …
        $this->assertSame((float) $cat['totalExpense'], (float) $cat['breakdown']->sum('total'));
        // … and the detail list must contain exactly those rows.
        $this->assertSame((int) $cat['breakdown']->sum('count'), $cat['transactions']->flatten()->count());

        // Operating categories exclude transfers and wages; grand total = operating + wages.
        $this->assertSame(200.0, (float) $cat['totalExpense']);
        $this->assertSame(300.0, (float) $cat['wageExpense']);
        $this->assertSame(500.0, (float) $cat['grandTotal']);

        // Expense by Category agrees with the P&L definitions.
        $this->assertSame((float) $pl['expenses'], (float) $cat['totalExpense']);
        $this->assertSame((float) $pl['productionWages'], (float) $cat['wageExpense']);
    }

    public function test_labour_is_counted_once_in_the_pnl(): void
    {
        Order::create([
            'business_id' => $this->business->id, 'created_by' => $this->owner->id, 'status' => 'delivered',
            'payment_status' => 'paid', 'payment_method' => 'cash', 'customer_name' => 'Buyer',
            'subtotal' => 1000, 'total' => 1000, 'paid_amount' => 1000, 'due_amount' => 0,
        ]);

        // Worker paid 300 cash; the same work also earns 300 of piece-rate (production_entries).
        Transaction::create([
            'business_id' => $this->business->id, 'created_by' => $this->owner->id, 'type' => 'expense',
            'title' => 'Salary payment', 'amount' => 300, 'payment_method' => 'cash',
            'reference' => 'EMPPAY-1', 'transaction_date' => now()->toDateString(),
        ]);
        $employee = Employee::create([
            'business_id' => $this->business->id, 'name' => 'Worker', 'salary' => 0,
            'type' => 'production', 'status' => 'active',
        ]);
        ProductionEntry::create([
            'business_id' => $this->business->id, 'employee_id' => $employee->id,
            'entry_date' => now()->toDateString(), 'quantity' => 3, 'amount' => 300,
        ]);

        $pl = $this->svc()->getProfitLoss($this->business->id, $this->from, $this->to);

        $this->assertSame(1000.0, (float) $pl['revenue']);
        $this->assertSame(0.0, (float) $pl['expenses']);          // wage payment is not an operating category
        $this->assertSame(300.0, (float) $pl['productionWages']); // cash paid, counted once
        $this->assertSame(700.0, (float) $pl['netProfit']);       // 1000 - 300; production_entries not added again
    }

    public function test_deleting_a_wage_transaction_removes_it_from_wages(): void
    {
        $txn = Transaction::create([
            'business_id' => $this->business->id, 'created_by' => $this->owner->id, 'type' => 'expense',
            'title' => 'Salary payment', 'amount' => 300, 'payment_method' => 'cash',
            'reference' => 'EMPPAY-1', 'transaction_date' => now()->toDateString(),
        ]);
        $this->assertSame(300.0, (float) $this->svc()->getProfitLoss($this->business->id, $this->from, $this->to)['productionWages']);

        $txn->delete(); // reversal

        $this->assertSame(0.0, (float) $this->svc()->getProfitLoss($this->business->id, $this->from, $this->to)['productionWages']);
    }

    public function test_business_overview_expense_matches_pnl_total_for_the_month(): void
    {
        $this->expense(100, null);
        $this->expense(999, 'TRANSFER-1');
        $this->income(999, 'TRANSFER-1');

        $svc = $this->svc();
        $pl  = $svc->getProfitLoss($this->business->id, $this->from, $this->to);
        $ov  = $svc->getBusinessOverview($this->business->id, (int) now()->year);

        $month = collect($ov['months'])->firstWhere('month', now()->format('F'));

        // Overview expense = operating + wages (cash) = P&L total expenses; transfers excluded.
        $this->assertSame((float) ($pl['expenses'] + $pl['productionWages']), (float) $month['expense']);
        $this->assertSame(100.0, (float) $month['expense']);
    }

    public function test_expense_category_page_shows_operating_plus_wages_not_transfers(): void
    {
        $this->actingAs($this->owner);
        $this->expense(1000, null);
        $this->expense(500, 'TRANSFER-1');

        $this->get(route('reports.expense-category', ['from' => $this->from, 'to' => $this->to]))
            ->assertOk()
            ->assertSee('৳1,000')
            ->assertDontSee('৳1,500');
    }

    public function test_csv_export_total_agrees_with_the_report(): void
    {
        $this->actingAs($this->owner);
        $this->expense(120, null);
        $this->expense(80, 'INV-1');
        Transaction::create([
            'business_id' => $this->business->id, 'created_by' => $this->owner->id, 'type' => 'expense',
            'title' => 'Salary payment', 'amount' => 300, 'payment_method' => 'cash',
            'reference' => 'EMPPAY-1', 'transaction_date' => now()->toDateString(),
        ]);

        $cat = $this->svc()->getExpenseCategory($this->business->id, $this->from, $this->to);

        $response = $this->get(route('reports.export', [
            'type' => 'expense-category', 'format' => 'csv', 'from' => $this->from, 'to' => $this->to,
        ]))->assertOk();

        $body = $response->streamedContent();
        $this->assertStringContainsString('GRAND TOTAL (operating + wages)', $body);
        $this->assertStringContainsString(number_format($cat['grandTotal'], 2), $body);
    }
}
