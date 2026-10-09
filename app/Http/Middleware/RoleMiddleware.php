<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Restrict a route to one or more user roles, e.g. ->middleware('role:owner,admin').
 *
 * Used to keep destructive/irreversible money actions (deleting orders, processing
 * refunds, paying suppliers, adjusting stock) out of the hands of lower roles even
 * when they hold the relevant module permission.
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = auth()->user();

        if (!$user || !in_array($user->role, $roles, true)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
