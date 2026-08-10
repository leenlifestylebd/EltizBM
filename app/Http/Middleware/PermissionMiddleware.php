<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    /**
     * Gate a route group behind a module permission.
     * Usage: ->middleware('permission:accounts')
     *
     * owner/admin always pass. Workers (role=employee) are deliberately skipped
     * here because BusinessMiddleware already confines them to the worker route
     * allowlist plus whatever modules they were granted — gating them twice would
     * lock them out of their own dashboard. Everyone else must hold the module.
     */
    public function handle(Request $request, Closure $next, string $module)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'You do not have access to this section.');
        }

        if ($user->role === 'employee') {
            return $next($request);
        }

        if (!$user->hasPermission($module)) {
            abort(403, 'You do not have access to this section.');
        }

        return $next($request);
    }
}
