<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Apply the tenant's timezone to date/time calculations for the request.
 *
 * The app default is UTC, but a business operates in its own timezone (default
 * Asia/Dhaka), so "today", report date boundaries and stored transaction dates must use
 * the business's local day — otherwise a sale after local midnight lands on the wrong
 * day in reports.
 */
class SetBusinessTimezone
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('business.apply_timezone', false)) {
            return $next($request);
        }

        $timezone = auth()->user()?->business?->timezone;

        if (is_string($timezone) && $timezone !== '' && in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        return $next($request);
    }
}
