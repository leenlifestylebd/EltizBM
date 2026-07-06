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
        view()->share('themePalette', $business->themePalette());
        view()->share('themeSettings', $business->theme_settings ?? []);

        // Worker/Employee role → worker dashboard, plus any back-office modules
        // explicitly granted to this account via Settings → Users (permissions).
        if ($user->role === 'employee') {
            $workerRoutes = [
                'production.worker.dashboard',
                'production.store',
                'production.tasks.submit',
                'production.tasks.my',
                'production.notifications.read',
                'logout',
            ];
            $routeName = optional($request->route())->getName();

            if (!in_array($routeName, $workerRoutes)) {
                $perms  = $user->permissions ?? [];
                $module = $this->moduleForRoute($routeName);
                // Empty perms = pure worker (locked). Granted perms unlock those modules.
                $allowed = !empty($perms)
                    && ($routeName === 'dashboard' || ($module && in_array($module, $perms)));

                if (!$allowed) {
                    return redirect()->route('production.worker.dashboard');
                }
            }
        }

        return $next($request);
    }

    /** Map a route name to its permission module key (see User::MODULES). */
    private function moduleForRoute(?string $name): ?string
    {
        if (!$name) return null;
        return match (explode('.', $name)[0]) {
            'orders', 'wholesale', 'web-orders', 'returns', 'tailoring' => 'orders',
            'pos'                          => 'pos',
            'products', 'categories', 'brands', 'attributes' => 'products',
            'inventory', 'materials'       => 'inventory',
            'accounts'                     => 'accounts',
            'reports'                      => 'reports',
            'people'                       => 'people',
            'employees', 'production', 'attendance' => 'employees',
            'steadfast', 'courier'         => 'courier',
            'settings'                     => 'settings',
            'integrations'                 => 'integrations',
            default                        => null,
        };
    }
}