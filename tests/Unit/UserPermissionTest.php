<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

/**
 * Guards the RBAC fix from Phase 4.
 *
 * hasPermission() used to return true whenever a user's permissions were empty,
 * which silently gave every manager/staff account access to every module. These
 * tests pin the deny-by-default behaviour so that fallback cannot come back.
 */
class UserPermissionTest extends TestCase
{
    private function user(string $role, ?array $permissions): User
    {
        $user = new User();
        $user->role        = $role;
        $user->permissions = $permissions;

        return $user;
    }

    public function test_owner_and_admin_bypass_module_permissions(): void
    {
        $this->assertTrue($this->user('owner', null)->hasPermission('accounts'));
        $this->assertTrue($this->user('admin', [])->hasPermission('settings'));
    }

    public function test_staff_without_permissions_is_denied(): void
    {
        $this->assertFalse($this->user('staff', [])->hasPermission('accounts'));
        $this->assertFalse($this->user('staff', null)->hasPermission('accounts'));
        $this->assertFalse($this->user('manager', [])->hasPermission('reports'));
    }

    public function test_staff_is_allowed_only_granted_modules(): void
    {
        $user = $this->user('staff', ['orders', 'pos']);

        $this->assertTrue($user->hasPermission('orders'));
        $this->assertTrue($user->hasPermission('pos'));
        $this->assertFalse($user->hasPermission('accounts'));
        $this->assertFalse($user->hasPermission('settings'));
    }

    public function test_every_module_key_is_answerable(): void
    {
        $user = $this->user('staff', array_keys(User::MODULES));

        foreach (array_keys(User::MODULES) as $module) {
            $this->assertTrue($user->hasPermission($module), "module {$module} should be granted");
        }
    }
}
