<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminGateController extends Controller
{
    // ── Show secret login page ─────────────────────────────────
    public function show(string $secret)
    {
        if (!$this->validSecret($secret)) abort(404);

        // Already logged in as super admin → go straight in
        if (auth()->check() && $this->isSuperAdmin(auth()->user()->email)) {
            return redirect()->route('super-admin.index');
        }

        return view('super-admin.gate', compact('secret'));
    }

    // ── Process login ──────────────────────────────────────────
    public function login(Request $request, string $secret)
    {
        if (!$this->validSecret($secret)) abort(404);

        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Must be in super admin list
        if (!$this->isSuperAdmin($request->email)) {
            return back()->withErrors(['email' => 'Access denied.'])->onlyInput('email');
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], true)) {
            $user = Auth::user();

            if (!$user->is_approved || !$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Account is inactive.'])->onlyInput('email');
            }

            $request->session()->regenerate();
            $user->update(['last_login_at' => now()]);

            return redirect()->route('super-admin.index');
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    // ── Helpers ────────────────────────────────────────────────
    private function validSecret(string $secret): bool
    {
        $configured = (string) config('services.super_admin.secret', '');
        return $configured !== '' && hash_equals($configured, $secret);
    }

    private function isSuperAdmin(string $email): bool
    {
        $allowed = array_filter(array_map('trim', explode(',', (string) config('services.super_admin.emails', ''))));
        return in_array($email, $allowed, true);
    }
}
