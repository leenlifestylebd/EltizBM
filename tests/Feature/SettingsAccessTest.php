<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsAccessTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'Access Test', 'slug' => 'access-test', 'trial_ends_at' => now()->addWeek()]);
    }

    private function user(string $role, array $permissions): User
    {
        return User::create([
            'business_id' => $this->business->id, 'name' => ucfirst($role), 'email' => $role . '-' . uniqid() . '@example.test',
            'password' => 'password', 'role' => $role, 'permissions' => $permissions,
            'is_active' => true, 'is_approved' => true,
        ]);
    }

    public function test_staff_without_settings_permission_cannot_open_or_change_general_settings(): void
    {
        $staff = $this->user('staff', []);

        $this->actingAs($staff)->get(route('settings.general'))->assertForbidden();
        $this->actingAs($staff)->post(route('settings.general.update'), ['name' => 'Hacked'])->assertForbidden();
    }

    public function test_owner_can_open_general_settings(): void
    {
        $owner = $this->user('owner', []);

        $this->actingAs($owner)->get(route('settings.general'))->assertOk();
    }
}
