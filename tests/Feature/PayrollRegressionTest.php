<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Models\EmployeeSalary;
use App\Models\PayrollPeriod;
use App\Models\ProductionEntry;
use App\Models\ProductionRate;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayrollRegressionTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-09-11 10:00:00'));
        $business = Business::create(['name' => 'Payroll Test', 'slug' => 'payroll-test', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $business->id, 'name' => 'Owner', 'email' => 'owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->employee = Employee::create([
            'business_id' => $business->id, 'name' => 'Salary Test Employee', 'salary' => 12000,
            'joining_date' => '2026-08-01', 'type' => 'salary', 'status' => 'active',
        ]);
        $this->revision('2026-07-01', 6000);
        $this->revision('2026-08-01', 12000);
        $this->payment('2026-07-20', 2000);
        $this->payment('2026-08-20', 6200);
        $this->actingAs($this->owner);
    }

    public function test_salary_history_before_joining_date_carries_into_next_month(): void
    {
        $july = $this->employee->payrollLedger('2026-07-01', '2026-07-31');
        $august = $this->employee->payrollLedger('2026-08-01', '2026-08-31');
        $this->assertSame(4000.0, $july['closing']);
        $this->assertSame($july['closing'], $august['opening']);
        $this->assertSame(9800.0, $august['closing']);
        $this->employee->update(['joining_date' => null]);
        $this->assertSame(9800.0, $this->employee->fresh()->payrollLedger('2026-08-01', '2026-08-31')['closing']);
    }

    public function test_raise_and_zero_revision_do_not_rewrite_previous_months(): void
    {
        $this->revision('2026-09-01', 16000);
        $this->employee->update(['salary' => 16000]);
        $this->assertSame(25800.0, $this->employee->fresh()->payrollLedger('2026-09-01', '2026-09-30')['closing']);
        $this->revision('2026-10-01', 0);
        $this->employee->update(['salary' => 0, 'type' => 'production']);
        $this->detail('2026-07')->assertOk()->assertViewHas('monthSalary', 6000.0)->assertViewHas('runningDue', 4000.0);
        $this->detail('2026-08')->assertOk()->assertViewHas('runningDue', 9800.0);
        $this->assertSame(25800.0, $this->employee->fresh()->payrollLedger('2026-10-01', '2026-10-31')['closing']);
    }

    public function test_month_before_salary_history_never_uses_current_salary(): void
    {
        $this->assertSame(0.0, $this->employee->salaryForMonth('2026-06-01'));
        $this->employee->update(['salary' => 16000]);
        $this->detail('2026-06')->assertOk()->assertViewHas('monthEarned', 0.0)->assertViewHas('runningDue', 0.0);
    }

    public function test_legacy_employee_uses_a_stable_start_and_first_raise_preserves_old_rate(): void
    {
        $this->employee->salaries()->delete();
        $this->employee->update(['joining_date' => '2026-07-01', 'salary' => 6000]);
        $this->assertSame(4000.0, $this->employee->fresh()->payrollLedger('2026-08-01', '2026-08-31')['opening']);
        $this->updateSalary(['salary' => 16000, 'salary_effective_from' => '2026-09'])->assertRedirect();
        $fresh = $this->employee->fresh();
        $this->assertSame(6000.0, $fresh->salaryForMonth('2026-07-01'));
        $this->assertSame(6000.0, $fresh->salaryForMonth('2026-08-01'));
        $this->assertSame(16000.0, $fresh->salaryForMonth('2026-09-01'));
    }

    public function test_future_raise_and_contact_edit_preserve_current_rate_and_future_revision(): void
    {
        $this->updateSalary(['salary' => 16000, 'salary_effective_from' => '2026-10'])->assertRedirect();
        $this->assertSame(12000.0, (float) $this->employee->fresh()->salary);
        $this->updateSalary(['phone' => '0123456789'])->assertRedirect();
        $fresh = $this->employee->fresh();
        $this->assertSame(12000.0, $fresh->salaryForMonth('2026-09-01'));
        $this->assertSame(16000.0, $fresh->salaryForMonth('2026-10-01'));
        $this->assertSame('2026-08-01', $fresh->joining_date->toDateString());
    }

    public function test_switch_to_production_records_zero_even_when_salary_input_is_disabled(): void
    {
        $this->updateSalary(['type' => 'production', 'salary_effective_from' => '2026-09'])->assertRedirect();
        $fresh = $this->employee->fresh();
        $this->assertSame(0.0, $fresh->salaryForMonth('2026-09-01'));
        $this->assertSame(12000.0, $fresh->salaryForMonth('2026-08-01'));
        $this->assertSame(9800.0, $fresh->payrollLedger('2026-09-01', '2026-09-30')['closing']);
    }

    public function test_same_amount_backdated_revision_is_recorded_and_upsert_is_idempotent(): void
    {
        $data = ['salary' => 12000, 'salary_effective_from' => '2026-06'];
        $this->updateSalary($data)->assertRedirect();
        $this->updateSalary($data)->assertRedirect();
        $this->assertSame(1, $this->employee->salaries()->whereDate('effective_from', '2026-06-01')->count());
        $this->assertSame(12000.0, $this->employee->fresh()->salaryForMonth('2026-06-01'));
        $this->assertSame(6000.0, $this->employee->fresh()->salaryForMonth('2026-07-01'));
    }

    public function test_bonus_is_separate_and_advance_and_deduction_settle_due_once(): void
    {
        $this->payment('2026-08-21', 1000, 'bonus');
        $this->payment('2026-08-22', 500, 'advance');
        $this->payment('2026-08-23', 200, 'deduction');
        $august = $this->employee->payrollLedger('2026-08-01', '2026-08-31');
        $this->assertSame(9100.0, $august['closing']);
        $this->assertSame(6700.0, $august['paid']);
        $this->assertSame(1000.0, $august['bonus']);
        $this->assertSame($august['closing'], $this->employee->payrollLedger('2026-09-01', '2026-09-30')['opening']);
        $this->detail('2026-08')->assertOk()->assertViewHas('runningDue', 9100.0)->assertSee('Bonus paid (separate)')->assertSee('Deductions (-)');
        $this->get(route('production.payroll', ['month' => '2026-08']))->assertOk()
            ->assertViewHas('employees', fn ($rows) => $rows->first()->net_payable === 9100.0);
    }

    public function test_detail_payroll_list_and_dashboard_agree_on_august_due(): void
    {
        $this->detail('2026-08')->assertOk()->assertViewHas('runningDue', 9800.0)->assertViewHas('carryOver', -4000.0);
        $this->get(route('production.worker.ledger', ['month' => '2026-08']))->assertOk()
            ->assertViewHas('workers', fn ($rows) => $rows->first()['month_earned'] === 12000.0 && $rows->first()['cumulative_balance'] === 9800.0);
        $this->get(route('production.payroll', ['month' => '2026-08']))->assertOk()
            ->assertViewHas('employees', fn ($rows) => $rows->first()->net_payable === 9800.0);
        $this->get(route('production.worker.dashboard', ['employee_id' => $this->employee->id, 'from' => '2026-08-01', 'to' => '2026-08-31']))
            ->assertOk()->assertViewHas('netPayable', 9800.0)->assertViewHas('openingBalance', 4000.0);
        $this->get(route('production.admin-overview', ['month' => '2026-08']))->assertOk()
            ->assertViewHas('employeeStats', fn ($rows) => $rows->first()['net_payable'] === 9800.0);
        $this->detail('2026-07')->assertSee('6,000 salary')->assertDontSee('12,000 salary');
    }

    public function test_csv_and_pdf_include_salary_and_valid_production_breakdown(): void
    {
        $this->production('2026-08-15', 300);
        $this->payment('2026-08-21', 1000, 'bonus');
        $csv = $this->get(route('production.worker.ledger.export', [$this->employee, 'month' => '2026-08', 'format' => 'csv']))->assertOk()->streamedContent();
        $this->assertStringContainsString('"Base salary","12,000.00"', $csv);
        $this->assertStringContainsString('"Total Earned","12,300.00"', $csv);
        $this->assertStringContainsString('"Due (All-time)","10,100.00"', $csv);
        $this->assertStringContainsString('"Bonus paid (separate)","1,000.00"', $csv);
        Pdf::shouldReceive('loadView')->once()->withArgs(function ($view, $data) {
            $this->assertSame(10100.0, $data['runningDue']);
            $this->assertSame(12000.0, $data['monthSalary']);
            $html = view($view, $data)->render();
            $this->assertStringContainsString('Test Product', $html);
            $this->assertStringContainsString('10,100', $html);
            return true;
        })->andReturnSelf();
        Pdf::shouldReceive('setPaper')->once()->with('a4', 'portrait')->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('verified PDF data'));
        $this->get(route('production.worker.ledger.export', [$this->employee, 'month' => '2026-08', 'format' => 'pdf']))->assertOk();
    }

    public function test_future_payments_do_not_change_an_earlier_month_balance(): void
    {
        $this->payment('2026-09-01', 9000);
        $this->get(route('production.worker.ledger', ['month' => '2026-08']))->assertOk()
            ->assertViewHas('workers', fn ($rows) => $rows->first()['cumulative_balance'] === 9800.0);
        $this->detail('2026-08')->assertViewHas('runningDue', 9800.0);
    }

    public function test_partial_ranges_credit_monthly_salary_only_once(): void
    {
        $first = $this->employee->payrollLedger('2026-08-01', '2026-08-15');
        $second = $this->employee->payrollLedger('2026-08-16', '2026-08-31');
        $this->assertSame(12000.0, $first['base']);
        $this->assertSame(0.0, $second['base']);
        $this->assertSame($first['closing'], $second['opening']);
        $this->assertSame(9800.0, $second['closing']);
    }

    public function test_production_only_overpayment_and_multi_month_ranges_are_preserved(): void
    {
        $this->employee->salaries()->delete();
        $this->employee->payments()->delete();
        $this->employee->update(['salary' => 0, 'type' => 'production']);
        $this->production('2026-07-15', 500);
        $this->production('2026-08-15', 200);
        $this->payment('2026-07-20', 1000, 'advance');
        $this->payment('2026-08-20', 100, 'bonus');
        $ledger = $this->employee->fresh()->payrollLedger('2026-08-01', '2026-08-31');
        $this->assertSame(-500.0, $ledger['opening']);
        $this->assertSame(-300.0, $ledger['closing']);
        $this->assertSame(-300.0, $this->employee->fresh()->payrollLedger('2026-07-01', '2026-08-31')['closing']);
    }

    public function test_employee_pages_show_cumulative_due_and_effective_salary(): void
    {
        $this->revision('2026-09-01', 16000);
        $this->get(route('employees.index'))->assertOk()->assertViewHas('employees', fn ($rows) => $rows->first()->month_net === 25800.0);
        $this->get(route('employees.show', $this->employee))->assertOk()->assertViewHas('monthNetPayable', 25800.0)->assertViewHas('monthBase', 16000.0);
        $this->get(route('employees.edit', $this->employee))->assertOk()->assertSee('salary_effective_from');
    }

    public function test_payment_failure_rolls_back_cash_book_expense(): void
    {
        DB::statement("CREATE TRIGGER fail_payment BEFORE INSERT ON employee_payments BEGIN SELECT RAISE(ABORT, 'simulated payment failure'); END");
        $before = Transaction::count();
        $this->post(route('production.payment.store'), $this->paymentRequest())->assertStatus(500);
        $this->assertSame($before, Transaction::count());
        $this->assertSame(2, $this->employee->payments()->count());
    }

    public function test_payment_and_cash_book_delete_roll_back_together_on_failure(): void
    {
        $this->post(route('production.payment.store'), $this->paymentRequest())->assertRedirect();
        $payment = EmployeePayment::latest('id')->firstOrFail();
        $this->assertNotNull($payment->transaction_id);
        DB::statement("CREATE TRIGGER fail_delete BEFORE DELETE ON employee_payments BEGIN SELECT RAISE(ABORT, 'simulated delete failure'); END");
        $this->delete(route('production.payment.destroy', $payment))->assertStatus(500);
        $this->assertNotNull($payment->fresh());
        $this->assertNotNull(Transaction::find($payment->transaction_id));
    }

    public function test_salary_history_failure_rolls_back_employee_edit(): void
    {
        DB::statement("CREATE TRIGGER fail_salary BEFORE INSERT ON employee_salaries BEGIN SELECT RAISE(ABORT, 'simulated revision failure'); END");
        $this->updateSalary(['salary' => 16000, 'salary_effective_from' => '2026-09', 'phone' => '0123456789'])->assertStatus(500);
        $this->assertSame(12000.0, (float) $this->employee->fresh()->salary);
        $this->assertNull($this->employee->fresh()->phone);
        $this->assertSame(2, $this->employee->salaries()->count());
    }

    public function test_finalization_uses_server_totals_instead_of_submitted_amounts(): void
    {
        $this->post(route('production.payroll.finalize', $this->employee), [
            'month' => '2026-08', 'gross_earning' => 1, 'total_paid' => 1, 'net_due' => 0, 'total_pieces' => 900,
        ])->assertRedirect();
        $period = PayrollPeriod::firstOrFail();
        $this->assertTrue($period->is_finalized);
        $this->assertSame(12000.0, (float) $period->gross_earning);
        $this->assertSame(6200.0, (float) $period->total_paid);
        $this->assertSame(9800.0, (float) $period->net_due);
        $this->assertSame(0, $period->total_pieces);
    }

    public function test_employee_cannot_access_another_business_ledger_or_pay_it(): void
    {
        $other = Business::create(['name' => 'Other', 'slug' => 'other']);
        $employee = Employee::create(['business_id' => $other->id, 'name' => 'Other employee', 'salary' => 999, 'status' => 'active']);
        $this->get(route('production.worker.ledger.detail', [$employee, 'month' => '2026-08']))->assertForbidden();
        $this->postJson(route('production.payment.store'), array_replace($this->paymentRequest(), ['employee_id' => $employee->id]))->assertUnprocessable();
    }

    public function test_last_day_payments_and_production_are_included_in_every_monthly_view(): void
    {
        $this->payment('2026-08-31', 800);
        $this->production('2026-08-31', 300);
        $this->detail('2026-08')->assertOk()->assertViewHas('monthPaid', 7000.0)
            ->assertViewHas('monthEarned', 12300.0)->assertViewHas('runningDue', 9300.0)
            ->assertViewHas('payments', fn ($rows) => $rows->count() === 2)
            ->assertViewHas('entries', fn ($rows) => $rows->count() === 1);
        $this->get(route('production.worker.ledger', ['month' => '2026-08']))->assertOk()
            ->assertViewHas('workers', fn ($rows) => $rows->first()['month_qty'] === 10 && $rows->first()['cumulative_balance'] === 9300.0);
        $this->assertSame(9300.0, $this->employee->fresh()->payrollLedger('2026-09-01', '2026-09-30')['opening']);
    }

    public function test_legacy_employee_without_joining_date_uses_creation_month(): void
    {
        $this->employee->salaries()->delete();
        $this->employee->update(['joining_date' => null]);
        $this->employee->forceFill(['created_at' => '2026-07-19 12:00:00'])->save();
        $fresh = $this->employee->fresh();
        $this->assertSame(0.0, $fresh->salaryForMonth('2026-06-01'));
        $this->assertSame(12000.0, $fresh->salaryForMonth('2026-07-01'));
        $this->assertSame(15800.0, $fresh->payrollLedger('2026-08-01', '2026-08-31')['closing']);
    }

    public function test_invalid_month_is_rejected_and_inverted_model_range_is_rejected(): void
    {
        $this->getJson(route('production.worker.ledger', ['month' => '2026-99']))->assertUnprocessable();
        $this->expectException(\InvalidArgumentException::class);
        $this->employee->payrollLedger('2026-09-02', '2026-09-01');
    }

    public function test_edit_form_uses_effective_salary_after_a_scheduled_raise(): void
    {
        $this->updateSalary(['salary' => 16000, 'salary_effective_from' => '2026-10'])->assertRedirect();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-11'));
        $this->get(route('employees.edit', $this->employee))->assertOk()->assertSee('value="16000"', false);
    }

    public function test_contact_edit_does_not_bring_forward_a_scheduled_production_conversion(): void
    {
        $this->updateSalary(['type' => 'production', 'salary_effective_from' => '2026-10'])->assertRedirect();
        $this->updateSalary(['phone' => '0123456789', 'type' => 'production'])->assertRedirect();
        $fresh = $this->employee->fresh();
        $this->assertSame(12000.0, $fresh->salaryForMonth('2026-09-01'));
        $this->assertSame(0.0, $fresh->salaryForMonth('2026-10-01'));
    }

    private function revision(string $from, float $amount): void
    {
        $this->employee->salaries()->create(['business_id' => $this->employee->business_id, 'amount' => $amount, 'effective_from' => $from]);
        $this->employee->unsetRelation('salaries');
    }

    private function payment(string $date, float $amount, string $type = 'salary'): void
    {
        $this->employee->payments()->create(['business_id' => $this->employee->business_id, 'created_by' => $this->owner->id, 'payment_date' => $date, 'amount' => $amount, 'type' => $type, 'payment_method' => 'cash']);
    }

    private function production(string $date, float $amount): void
    {
        $rate = ProductionRate::create(['business_id' => $this->employee->business_id, 'product_name' => 'Test Product', 'rate' => $amount / 10, 'is_active' => true]);
        $this->employee->productionEntries()->create(['business_id' => $this->employee->business_id, 'production_rate_id' => $rate->id, 'entry_date' => $date, 'quantity' => 10, 'amount' => $amount]);
    }

    private function detail(string $month)
    {
        return $this->get(route('production.worker.ledger.detail', [$this->employee, 'month' => $month]));
    }

    private function updateSalary(array $data)
    {
        return $this->put(route('employees.update', $this->employee), ['name' => $this->employee->name] + $data);
    }

    private function paymentRequest(): array
    {
        return ['employee_id' => $this->employee->id, 'amount' => 100, 'type' => 'salary', 'payment_date' => '2026-08-25', 'payment_method' => 'cash'];
    }
}
