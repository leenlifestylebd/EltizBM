<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollLockTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $owner;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'Payroll Lock', 'slug' => 'payroll-lock', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $this->business->id, 'name' => 'Owner', 'email' => 'plock-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->employee = Employee::create([
            'business_id' => $this->business->id, 'name' => 'Worker', 'salary' => 12000,
            'type' => 'salary', 'status' => 'active',
        ]);
        $this->actingAs($this->owner);
    }

    private function finalizeThisMonth(): void
    {
        PayrollPeriod::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'period_month' => now()->format('Y-m'), 'is_finalized' => true,
        ]);
    }

    public function test_a_payment_cannot_be_added_to_a_finalized_period(): void
    {
        $this->finalizeThisMonth();

        $this->post(route('production.payment.store'), [
            'employee_id' => $this->employee->id, 'amount' => 5000, 'type' => 'salary',
            'payment_date' => now()->toDateString(), 'payment_method' => 'cash',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, EmployeePayment::where('employee_id', $this->employee->id)->count());
    }

    public function test_a_payment_in_a_finalized_period_cannot_be_deleted(): void
    {
        $this->finalizeThisMonth();
        $payment = EmployeePayment::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'created_by' => $this->owner->id, 'payment_date' => now()->toDateString(),
            'amount' => 5000, 'type' => 'salary', 'payment_method' => 'cash',
        ]);

        $this->delete(route('production.payment.destroy', $payment))->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseHas('employee_payments', ['id' => $payment->id]);
    }

    public function test_payments_are_allowed_when_the_period_is_not_finalized(): void
    {
        $this->post(route('production.payment.store'), [
            'employee_id' => $this->employee->id, 'amount' => 5000, 'type' => 'advance',
            'payment_date' => now()->toDateString(), 'payment_method' => 'cash',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1, EmployeePayment::where('employee_id', $this->employee->id)->count());
    }
}
