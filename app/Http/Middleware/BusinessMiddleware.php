<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class BusinessMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if (!$user || !$user->business_id) return redirect()->route('login');
        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->withErrors(['email'=>'Your account has been deactivated.']);
        }
        $business = $user->business;
        view()->share('business', $business);
        view()->share('currentUser', $user);
        view()->share('trialDaysLeft', $business->trialDaysLeft());
        view()->share('isOnTrial', $business->isOnTrial());

        // Worker/Employee role → only production dashboard
        if ($user->role === 'employee') {
            $allowed = [
                'production.worker.dashboard',
                'production.store',
                'logout',
            ];
            if (!in_array($request->route()->getName(), $allowed)) {
                return redirect()->route('production.worker.dashboard');
            }
        }

        return $next($request);
    }
}