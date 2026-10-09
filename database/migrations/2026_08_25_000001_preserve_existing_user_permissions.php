<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Before route-level enforcement, empty permissions meant full access.
        // Preserve that behaviour for existing manager/staff accounts while making
        // future empty permission sets correctly mean no module access.
        User::query()
            ->whereIn('role', ['manager', 'staff'])
            ->where(function ($query) {
                $query->whereNull('permissions')
                    ->orWhere('permissions', '[]');
            })
            ->eachById(fn (User $user) => $user->update([
                'permissions' => array_keys(User::MODULES),
            ]));
    }

    public function down(): void
    {
        // Access data cannot be safely inferred and should not be erased on rollback.
    }
};
