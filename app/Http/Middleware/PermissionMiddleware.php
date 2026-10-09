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

        // BusinessMiddleware already restricts employees to these worker routes.
        // Let those exact routes pass without granting the whole employees module.
        if ($user?->role === 'employee' && in_array($request->route()?->getName(), [
            'production.worker.dashboard',
            'production.tasks.submit',
            'production.tasks.my',
            'production.notifications.read',
        ], true)) {
            return $next($request);
        }

        $modules = array_filter(explode('|', $module));
        if (!$user || !collect($modules)->contains(fn (string $candidate) => $user->hasPermission($candidate))) {
            abort(403, 'You do not have access to this section.');
        }

        return $next($request);
    }
}
