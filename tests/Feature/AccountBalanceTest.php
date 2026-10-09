<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Business;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountBalanceTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $business = Business::create(['name' => 'Balance Test', 'slug' => 'balance-test', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $business->id, 'name' => 'Owner', 'email' => 'balance-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->actingAs($this->owner);
    }

    private function income(float $amount, string $date, string $method = 'cash'): void
    {
        Transaction::create([
            'business_id' => $this->owner->business_id, 'created_by' => $this->owner->id,
            'type' => 'income', 'title' => 'Income', 'amount' => $amount,
            'payment_method' => $method, 'transaction_date' => $date,
        ]);
    }

    public function test_the_balance_can_be_filtered_by_date(): void
    {
        $this->income(100, now()->toDateString());
        $this->income(500, now()->subMonths(2)->toDateString());

        // All time — both transactions count.
        $this->get(route('reports.account-balance'))->assertOk()->assertSee('৳600');

        // Filtered to today — only the recent 100 counts.
        $this->get(route('reports.account-balance', ['from' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('৳100')
            ->assertDontSee('৳600');
    }

    public function test_transfers_are_excluded_and_new_methods_are_included(): void
    {
        Transaction::create([
            'business_id' => $this->owner->business_id, 'created_by' => $this->owner->id, 'type' => 'income',
            'title' => 'Custom', 'amount' => 70, 'payment_method' => 'custom_wallet', 'transaction_date' => now()->toDateString(),
        ]);
        // Transfer legs must not inflate any account.
        Transaction::create([
            'business_id' => $this->owner->business_id, 'created_by' => $this->owner->id, 'type' => 'expense',
            'title' => 'Transfer out', 'amount' => 999, 'payment_method' => 'cash', 'reference' => 'TRANSFER-1', 'transaction_date' => now()->toDateString(),
        ]);

        $this->get(route('reports.account-balance'))
            ->assertOk()
            ->assertSee('Custom_wallet')
            ->assertDontSee('৳999');
    }

    public function test_opening_balance_is_added_to_the_method_balance(): void
    {
        Account::create([
            'business_id' => $this->owner->business_id, 'name' => 'Cash', 'method' => 'cash',
            'type' => 'cash', 'opening_balance' => 1000, 'is_active' => true,
        ]);
        $this->income(100, now()->toDateString());

        $this->get(route('reports.account-balance'))->assertOk()->assertSee('৳1,100');
    }

    public function test_owner_can_save_opening_balances_but_staff_cannot(): void
    {
        $this->post(route('reports.account-balance.opening'), ['opening' => ['cash' => 250]])
            ->assertRedirect();
        $this->assertSame(250.0, (float) Account::where('business_id', $this->owner->business_id)->where('method', 'cash')->value('opening_balance'));

        $staff = User::create([
            'business_id' => $this->owner->business_id, 'name' => 'Staff', 'email' => 'staff-bal@example.test',
            'password' => 'password', 'role' => 'staff', 'permissions' => ['reports'],
            'is_active' => true, 'is_approved' => true,
        ]);
        $this->actingAs($staff)
            ->post(route('reports.account-balance.opening'), ['opening' => ['cash' => 999]])
            ->assertForbidden();
    }
}
