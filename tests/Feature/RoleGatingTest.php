<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleGatingTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $owner;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'Role Test', 'slug' => 'role-test', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $this->business->id, 'name' => 'Owner', 'email' => 'role-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->order = Order::create([
            'business_id' => $this->business->id, 'created_by' => $this->owner->id,
            'status' => 'delivered', 'payment_status' => 'paid', 'payment_method' => 'cash',
            'customer_name' => 'Buyer', 'subtotal' => 100, 'total' => 100, 'paid_amount' => 100, 'due_amount' => 0,
        ]);
    }

    private function user(string $role, array $permissions): User
    {
        return User::create([
            'business_id' => $this->business->id, 'name' => ucfirst($role),
            'email' => $role . '-' . uniqid() . '@example.test', 'password' => 'password',
            'role' => $role, 'permissions' => $permissions, 'is_active' => true, 'is_approved' => true,
        ]);
    }

    public function test_staff_cannot_perform_destructive_money_actions_even_with_module_permission(): void
    {
        $staff = $this->user('staff', ['orders', 'inventory', 'accounts']);
        $this->actingAs($staff);

        $this->delete(route('orders.destroy', $this->order))->assertForbidden();
        $this->post(route('orders.status', $this->order), ['status' => 'cancelled'])->assertForbidden();
        $this->post(route('returns.request', $this->order), ['reason' => 'x'])->assertForbidden();
        $this->post(route('inventory.quick-adjust'), ['product_id' => 1, 'delta' => 1])->assertForbidden();
    }

    public function test_a_manager_is_allowed_through_the_role_gate(): void
    {
        $manager = $this->user('manager', ['orders']);
        $this->actingAs($manager);

        // Not forbidden by the role gate (validation/redirect may still apply).
        $this->post(route('returns.request', $this->order), ['reason' => 'Defective'])
            ->assertRedirect();
        $this->assertSame('return_requested', $this->order->fresh()->status);
    }
}
