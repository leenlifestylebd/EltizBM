<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;

class SettingsController extends Controller
{
    // =============================================
    // GENERAL (Profile)
    // =============================================
    public function general()
    {
        $user = auth()->user();
        return view('settings.general', compact('user'));
    }

    public function updateGeneral(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'     => 'required|string|max:100',
            'phone'    => 'nullable|string|max:20',
            'password' => ['nullable', 'confirmed', Rules\Password::min(8)],
        ]);

        $user->update([
            'name'  => $request->name,
            'phone' => $request->phone,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return back()->with('success', 'Profile updated!');
    }

    // =============================================
    // BUSINESS SETTINGS
    // =============================================
    public function business()
    {
        $business = auth()->user()->business;
        return view('settings.business', compact('business'));
    }

    public function updateBusiness(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:100',
            'phone'   => 'nullable|string|max:20',
            'email'   => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
            'domain'  => 'nullable|string|max:100',
            'vat_rate'=> 'nullable|numeric|min:0|max:100',
            'tax_rate'=> 'nullable|numeric|min:0|max:100',
        ]);

        $business = auth()->user()->business;

        $business->update([
            'name'           => $request->name,
            'phone'          => $request->phone,
            'email'          => $request->email,
            'address'        => $request->address,
            'domain'         => $request->domain,
            'currency'       => $request->currency ?? 'BDT',
            'currency_symbol'=> $request->currency_symbol ?? '৳',
            'timezone'       => $request->timezone ?? 'Asia/Dhaka',
            'vat_enabled'    => $request->boolean('vat_enabled'),
            'vat_rate'       => $request->vat_rate ?? 0,
            'vat_label'      => $request->vat_label ?? 'VAT',
            'vat_mode'       => $request->vat_mode ?? 'inclusive',
            'tax_enabled'    => $request->boolean('tax_enabled'),
            'tax_rate'       => $request->tax_rate ?? 0,
            'tax_label'      => $request->tax_label ?? 'Tax',
        ]);

        return back()->with('success', 'Business settings saved!');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate(['logo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048']);

        $business = auth()->user()->business;

        if ($business->logo) {
            Storage::disk('public')->delete($business->logo);
        }

        $path = $request->file('logo')->store("businesses/{$business->id}/logo", 'public');
        $business->update(['logo' => $path]);

        return back()->with('success', 'Logo uploaded!');
    }

    // =============================================
    // USER MANAGEMENT
    // =============================================
    public function userManagement()
    {
        $bizId       = auth()->user()->business_id;
        $users       = User::where('business_id', $bizId)->where('is_approved', 1)->orderBy('name')->get();
        $pendingUsers = User::where('is_approved', 0)->orderByDesc('created_at')->get();
        return view('settings.user-management', compact('users', 'pendingUsers'));
    }

    public function approveUser(User $user)
    {
        $user->update([
            'is_approved' => 1,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'is_active'   => true,
        ]);
        return back()->with('success', $user->name . ' approved successfully!');
    }

    public function rejectUser(User $user)
    {
        $user->delete();
        return back()->with('success', 'User rejected and removed.');
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required|in:admin,manager,staff,employee',
            'password' => ['required', Rules\Password::min(8)],
        ]);

        User::create([
            'business_id' => auth()->user()->business_id,
            'name'        => $request->name,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'role'        => $request->role,
            'permissions' => $request->permissions ?? [],
            'password'    => Hash::make($request->password),
            'is_active'   => true,
            'is_approved' => 1,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'User added!');
    }

    public function updateUser(Request $request, User $user)
    {
        abort_if($user->business_id !== auth()->user()->business_id, 403);

        $user->update([
            'name'        => $request->name,
            'phone'       => $request->phone,
            'role'        => $request->role,
            'is_active'   => $request->boolean('is_active', true),
            'permissions' => $request->permissions ?? [],
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return back()->with('success', 'User updated!');
    }

    public function destroyUser(User $user)
    {
        abort_if($user->business_id !== auth()->user()->business_id, 403);
        abort_if($user->id === auth()->id(), 403, 'Cannot delete yourself');

        $user->delete();
        return back()->with('success', 'User removed!');
    }

    // =============================================
    // SUBSCRIPTION
    // =============================================
    public function subscription()
    {
        $business = auth()->user()->business;
        return view('settings.subscription', compact('business'));
    }

    // =============================================
    // WAREHOUSES
    // =============================================
    public function warehouses()
    {
        $bizId      = auth()->user()->business_id;
        $warehouses = \App\Models\Warehouse::where('business_id', $bizId)->get();
        return view('settings.warehouses', compact('warehouses'));
    }

    // =============================================
    // STATUSES (custom order statuses)
    // =============================================
    public function statuses()
    {
        return view('settings.statuses');
    }

    // =============================================
    // TAGS
    // =============================================
    public function tags()
    {
        return view('settings.tags');
    }

    // =============================================
    // PRINT TEMPLATES
    // =============================================
    public function printTemplates()
    {
        $business = auth()->user()->business;
        return view('settings.print-templates', compact('business'));
    }

    // =============================================
    // KEYBOARD SHORTCUTS
    // =============================================
    public function keyboardShortcuts()
    {
        return view('settings.keyboard-shortcuts');
    }

    // =============================================
    // DELETION LOG
    // =============================================
    public function deletionLog()
    {
        $bizId = auth()->user()->business_id;
        $logs  = \App\Models\ActivityLog::where('business_id', $bizId)
            ->where('action', 'deleted')
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(20);
        return view('settings.deletion-log', compact('logs'));
    }

    // =============================================
    // PAYMENT ACCOUNTS
    // =============================================
    public function paymentAccounts()
    {
        $business = auth()->user()->business;
        return view('settings.payment-accounts', compact('business'));
    }

    // =============================================
    // PAYMENT HISTORY
    // =============================================
    public function paymentHistory()
    {
        $business = auth()->user()->business;
        return view('settings.payment-history', compact('business'));
    }
}
