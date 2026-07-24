<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $allowedEmails = array_filter(
            array_map('trim', explode(',', (string) config('services.super_admin.emails', '')))
        );

        if (empty($allowedEmails) || !in_array(auth()->user()->email, $allowedEmails, true)) {
            abort(403, 'Super Admin access denied.');
        }

        return $next($request);
    }
}
