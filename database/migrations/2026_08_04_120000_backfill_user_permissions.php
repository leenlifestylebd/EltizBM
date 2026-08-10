<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;

return new class extends Migration
{
    /**
     * hasPermission() used to return true whenever a user had no permissions set,
     * which meant module permissions could never actually restrict anyone. That
     * fallback is gone, so any existing manager/staff account with an empty
     * permissions array would suddenly be locked out of everything.
     *
     * Backfill those accounts with the full module list: their effective access is
     * unchanged (they had everything before), but it is now explicit and can be
     * trimmed from Settings -> Users. owner/admin bypass permissions entirely and
     * workers (role=employee) are governed by BusinessMiddleware, so both are left
     * untouched.
     */
    public function up(): void
    {
        $all = array_keys(User::MODULES);

        User::query()
            ->whereNotIn('role', ['owner', 'admin', 'employee'])
            ->get()
            ->each(function (User $user) use ($all) {
                if (empty($user->permissions)) {
                    $user->permissions = $all;
                    $user->save();
                }
            });
    }

    public function down(): void
    {
        // No-op: we don't want to strip permissions on rollback.
    }
};
