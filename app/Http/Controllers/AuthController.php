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

        // Match on the last 10 digits rather than the raw string: the same number
        // saved as 01712-345678 or +8801712345678 must accept 01712345678 at login.
        $key = User::phoneKey($request->phone);

        $candidates = $key === '' ? collect() : User::where('role', 'employee')
            ->where('is_active', true)
            ->where('is_approved', 1)
            ->where('phone', 'like', '%' . $key)
            ->orderByDesc('id')
            ->get()
            // LIKE '%key' can over-match (e.g. a longer number ending the same way),
            // so confirm the normalised keys really are equal.
            ->filter(fn ($u) => User::phoneKey($u->phone) === $key);

        $matches = $candidates->filter(fn ($u) => $u->password && Hash::check($request->pin, $u->password));

        // A 4-6 digit PIN is weak, so refuse when the same phone+PIN resolves to
        // more than one BUSINESS — logging into the wrong tenant would be worse
        // than a failed login. Duplicate accounts inside one business are a data
        // artefact, not a security question: take the newest and let them in.
        $businesses = $matches->pluck('business_id')->unique();
        $user = match (true) {
            $matches->isEmpty()      => null,
            $businesses->count() > 1 => null,
            default                  => $matches->first(),
        };

        if ($businesses->count() > 1) {
            \Illuminate\Support\Facades\Log::warning('Worker login refused — same phone+PIN across businesses', [
                'phone_key' => $key,
            ]);
        }

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
                // Rotating remember_token invalidates "remember me" cookies issued
                // before the reset, so a stolen session can't outlive the password.
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
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