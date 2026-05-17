<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    // ── Redirect to Google ────────────────────────────────────────────
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // ── Handle Google Callback ────────────────────────────────────────
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Google login failed. Please try again.']);
        }

        // ① Already exists with this google_id → log in
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            return $this->loginUser($user);
        }

        // ② Exists with same email but no google_id → link accounts
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar'    => $user->avatar ?: $googleUser->getAvatar(),
            ]);
            return $this->loginUser($user);
        }

        // ③ Brand new user → assign to the main/first business, pending approval
        // Find the first (oldest) business — the main business of this system
        $business = Business::orderBy('id')->first();

        if (! $business) {
            return redirect()->route('login')->withErrors([
                'email' => 'No business found. Please contact the administrator.',
            ]);
        }

        $user = User::create([
            'business_id' => $business->id,
            'name'        => $googleUser->getName(),
            'email'       => $googleUser->getEmail(),
            'google_id'   => $googleUser->getId(),
            'avatar'      => $googleUser->getAvatar(),
            'password'    => bcrypt(\Illuminate\Support\Str::random(32)),
            'role'        => 'staff',
            'is_active'   => false,
            'is_approved' => false,
        ]);

        return redirect()->route('login')->with('google_pending',
            'আপনার অ্যাকাউন্ট তৈরি হয়েছে। Admin approval এর পর access পাবেন।'
        );
    }

    // ── Login helper — checks approval ───────────────────────────────
    private function loginUser(User $user): \Illuminate\Http\RedirectResponse
    {
        // Not approved yet
        if (! $user->is_approved) {
            return redirect()->route('login')->with('google_pending',
                'আপনার অ্যাকাউন্ট এখনো admin approved হয়নি। অনুগ্রহ করে অপেক্ষা করুন।'
            );
        }

        // Not active
        if (! $user->is_active) {
            return redirect()->route('login')->withErrors(['email' => 'Your account has been deactivated.']);
        }

        Auth::login($user, true);

        $user->update(['last_login_at' => now()]);

        return redirect()->intended('/dashboard');
    }
}
