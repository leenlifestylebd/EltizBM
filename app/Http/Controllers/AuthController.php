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
    // =============================================
    // SHOW LOGIN
    // =============================================
    public function showLogin()
    {
        return view('auth.login');
    }

    // =============================================
    // LOGIN
    // =============================================
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Update last login
            Auth::user()->update(['last_login_at' => now()]);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    // =============================================
    // SHOW REGISTER
    // =============================================
    public function showRegister()
    {
        return view('auth.register');
    }

    // =============================================
    // REGISTER
    // =============================================
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
            'name'         => $request->business_name,
            'slug'         => Str::slug($request->business_name) . '-' . Str::random(5),
            'phone'        => $request->phone,
            'email'        => $request->email,
            'plan'         => 'basic',
            'trial_ends_at'=> now()->addDays(14),
        ]);

        // 2. Create owner user
        $user = User::create([
            'business_id'   => $business->id,
            'name'          => $request->name,
            'email'         => $request->email,
            'phone'         => $request->phone,
            'password'      => Hash::make($request->password),
            'role'          => 'owner',
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

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to Fulfillo! Your 14-day trial has started.');
    }

    // =============================================
    // LOGOUT
    // =============================================
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    // =============================================
    // FORGOT PASSWORD
    // =============================================
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

    // =============================================
    // PRIVATE HELPERS
    // =============================================
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
