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
     * owner/admin always pass; users without explicit permissions pass (backward
     * compatible); everyone else must have the module granted.
     */
    public function handle(Request $request, Closure $next, string $module)
    {
        $user = auth()->user();

        if (!$user || !$user->hasPermission($module)) {
            abort(403, 'You do not have access to this section.');
        }

        return $next($request);
    }
}
