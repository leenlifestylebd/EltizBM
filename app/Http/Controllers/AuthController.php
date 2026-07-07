<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Check if approved
            if (!$user->is_approved) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account is pending approval. Please wait for admin to approve your account.',
                ])->onlyInput('email');
            }

            // Check if active
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Contact admin.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            $user->update(['last_login_at' => now()]);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    // =============================================
    // WORKER LOGIN (phone + PIN) — low-hassle for factory workers
    // =============================================
    public function showWorkerLogin()
    {
        return view('auth.worker-login');
    }

    public function workerLogin(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'pin'   => 'required|string|max:6',
        ]);

        // Throttle by phone+IP to stop PIN guessing.
        $key = 'worker-login:' . $request->phone . '|' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
            $secs = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            return back()->withErrors(['phone' => "Too many attempts. Try again in {$secs}s."])->onlyInput('phone');
        }

        // A phone can (via re-linking or accidental duplicates) map to more than one
        // worker account. Log in whichever active account's PIN actually matches —
        // not just the newest by id, which rejected valid PINs once a second account
        // existed for the same number (login worked at first, then "wrong password").
        $candidates = User::where('role', 'employee')
            ->where('phone', $request->phone)
            ->where('is_active', true)
            ->where('is_approved', 1)
            ->orderByDesc('id')
            ->get();

        $user = $candidates->first(fn ($u) => $u->password && Hash::check($request->pin, $u->password));

        if (!$user) {
            \Illuminate\Support\Facades\RateLimiter::hit($key, 60);
            return back()->withErrors(['phone' => 'Wrong phone or PIN.'])->onlyInput('phone');
        }

        \Illuminate\Support\Facades\RateLimiter::clear($key);
        Auth::login($user, true);
        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        return redirect()->route('production.worker.dashboard');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'business_name' => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email',
            'phone'         => 'nullable|string|max:20',
            'password'      => ['required', 'confirmed', Rules\Password::min(8)],
        ]);

        // 1. Create business
        $business = Business::create([
            'name'          => $request->business_name,
            'slug'          => Str::slug($request->business_name) . '-' . Str::random(5),
            'phone'         => $request->phone,
            'email'         => $request->email,
            'plan'          => 'basic',
            'trial_ends_at' => now()->addDays(14),
        ]);

        // 2. Create owner user (pending approval)
        $user = User::create([
            'business_id'   => $business->id,
            'name'          => $request->name,
            'email'         => $request->email,
            'phone'         => $request->phone,
            'password'      => Hash::make($request->password),
            'role'          => 'owner',
            'is_approved'   => 0,
            'last_login_at' => now(),
        ]);

        // 3. Seed default account categories
        $this->seedDefaultCategories($business->id);

        // 4. Create default warehouse
        \App\Models\Warehouse::create([
            'business_id' => $business->id,
            'name'        => 'Main Warehouse',
            'is_default'  => true,
        ]);

        // Don't login — show pending message
        return redirect()->route('login')
            ->with('status', '✅ Account created! Your account is pending admin approval. You will be notified once approved.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($request->only('email'));
        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password reset successfully!')
            : back()->withErrors(['email' => [__($status)]]);
    }

    private function seedDefaultCategories(int $businessId): void
    {
        $incomeCategories  = ['Sales', 'Service', 'Other Income'];
        $expenseCategories = ['Purchase', 'Salary', 'Rent', 'Utility', 'Marketing', 'Transport', 'Other Expense'];

        foreach ($incomeCategories as $name) {
            \App\Models\AccountCategory::create([
                'business_id' => $businessId,
                'name'        => $name,
                'type'        => 'income',
            ]);
        }

        foreach ($expenseCategories as $name) {
            \App\Models\AccountCategory::create([
                'business_id' => $businessId,
                'name'        => $name,
                'type'        => 'expense',
            ]);
        }
    }
}