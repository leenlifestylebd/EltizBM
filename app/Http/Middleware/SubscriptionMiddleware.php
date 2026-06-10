<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces subscription/trial status.
 * - Owners can always access settings and subscription pages.
 * - Read-only dashboard is accessible for 7 days grace after trial ends.
 * - All other write actions are blocked after plan expires.
 */
class SubscriptionMiddleware
{
    // Routes always accessible regardless of subscription status
    private const ALWAYS_ALLOWED = [
        'settings.subscription',
        'settings.payment-history',
        'settings.general',
        'settings.business',
        'logout',
        'dashboard',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user     = auth()->user();
        $business = $user?->business;

        // No business = skip (handled by BusinessMiddleware)
        if (!$business) {
            return $next($request);
        }

        // Owners on active paid plan — always pass
        if ($business->plan !== 'basic' && !$business->isOnTrial()) {
            // Has active subscription
            if ($business->trial_ends_at === null || $business->trial_ends_at->isFuture()) {
                return $next($request);
            }
        }

        // Always allow trial (future trial_ends_at)
        if ($business->isOnTrial()) {
            return $next($request);
        }

        // Always allow certain routes
        $routeName = $request->route()?->getName() ?? '';
        foreach (self::ALWAYS_ALLOWED as $allowed) {
            if (str_starts_with($routeName, $allowed)) {
                return $next($request);
            }
        }

        // Grace period: 7 days after trial/plan expiry, allow read-only
        if ($business->trial_ends_at && $business->trial_ends_at->diffInDays(now()) <= 7) {
            // Block mutating HTTP verbs
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Your trial has ended. Please upgrade to continue.',
                    ], 402);
                }
                return redirect()->route('settings.subscription')
                    ->with('warning', 'Your trial has ended. Please upgrade your plan to continue using the ERP.');
            }
            // GET requests — allow but show banner (banner shown via $trialDaysLeft in layout)
            return $next($request);
        }

        // Trial fully expired > 7 days ago — block completely
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Subscription expired.'], 402);
        }

        return redirect()->route('settings.subscription')
            ->with('error', 'Your subscription has expired. Please renew to access the ERP.');
    }
}
