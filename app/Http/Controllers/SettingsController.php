<?php

namespace App\Http\Controllers;

use App\Models\AccountCategory;
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

        // Merge label_merchant_id into print_settings (preserve other print settings)
        $printSettings = $business->print_settings ?? [];
        $printSettings['label_merchant_id'] = trim($request->label_merchant_id ?? '');

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
            'print_settings' => $printSettings,
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
    // THEME SETTINGS
    // =============================================
    public function theme()
    {
        $business = auth()->user()->business;
        return view('settings.theme', compact('business'));
    }

    public function updateTheme(Request $request)
    {
        $request->validate([
            'primary_color'  => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sidebar_color'  => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'header_color'   => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $business = auth()->user()->business;

        $theme = $business->theme_settings ?? [];
        $theme['primary_color'] = $request->primary_color;
        $theme['sidebar_color'] = $request->sidebar_color ?: null;
        $theme['header_color']  = $request->header_color  ?: null;
        $theme['dark_sidebar']  = $request->boolean('dark_sidebar');

        $business->update(['theme_settings' => $theme]);

        return back()->with('success', 'Theme saved! Refresh the page to see changes.');
    }

    public function resetTheme()
    {
        $business = auth()->user()->business;
        $business->update(['theme_settings' => null]);
        return back()->with('success', 'Theme reset to default.');
    }

    // =============================================
    // USER MANAGEMENT
    // =============================================
    public function userManagement()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $bizId       = auth()->user()->business_id;
        $users       = User::where('business_id', $bizId)->where('is_approved', 1)->orderBy('name')->get();
        $pendingUsers = User::where('business_id', $bizId)
            ->where('is_approved', 0)
            ->orderByDesc('created_at')
            ->get();
        return view('settings.user-management', compact('users', 'pendingUsers'));
    }

    public function approveUser(Request $request, User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_if($user->business_id !== auth()->user()->business_id, 403);

        $user->update([
            'is_approved' => 1,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'is_active'   => true,
            'role'        => in_array($request->role, ['admin','manager','staff','employee'])
                                ? $request->role
                                : ($user->role ?: 'staff'),
        ]);
        return back()->with('success', $user->name . ' approved as ' . ucfirst($user->fresh()->role) . '!');
    }

    public function rejectUser(User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_if($user->business_id !== auth()->user()->business_id, 403);

        $user->delete();
        return back()->with('success', 'User rejected and removed.');
    }

    public function storeUser(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

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
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_if($user->business_id !== auth()->user()->business_id, 403);

        // Never allow promoting/demoting the business owner via this form,
        // and only allow assigning roles below owner.
        $request->validate([
            'role' => 'nullable|in:admin,manager,staff,employee',
        ]);

        $newRole = $user->role === 'owner'
            ? 'owner'
            : ($request->filled('role') ? $request->role : $user->role);

        $user->update([
            'name'        => $request->name,
            'phone'       => $request->phone,
            'role'        => $newRole,
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
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_if($user->business_id !== auth()->user()->business_id, 403);
        abort_if($user->id === auth()->id(), 403, 'Cannot delete yourself');

        $this->logActivity('deleted', $user, [
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ]);

        $user->delete();
        return back()->with('success', 'User removed!');
    }

    // =============================================
    // STATUSES (custom order statuses)
    // =============================================
    public function statuses()
    {
        $business = auth()->user()->business;
        $statusConfig = $business->status_config ?? [];
        return view('settings.statuses', compact('business', 'statusConfig'));
    }

    public function updateStatuses(Request $request)
    {
        $business = auth()->user()->business;
        $config = [];
        foreach ($request->statuses ?? [] as $key => $data) {
            $config[$key] = [
                'label' => $data['label'] ?? ucfirst($key),
                'color' => $data['color'] ?? '#6b7280',
            ];
        }
        $business->update(['status_config' => $config]);
        return back()->with('success', 'Status settings saved!');
    }

    // =============================================
    // TAGS
    // =============================================
    public function tags()
    {
        $business = auth()->user()->business;
        $tags = $business->tags ?? [];
        return view('settings.tags', compact('business', 'tags'));
    }

    public function storeTag(Request $request)
    {
        $request->validate(['name' => 'required|string|max:50']);
        $business = auth()->user()->business;
        $tags   = $business->tags ?? [];
        $tags[] = ['name' => $request->name, 'color' => $request->color ?? '#3b82f6'];
        $business->update(['tags' => array_values($tags)]);
        return back()->with('success', 'Tag added!');
    }

    public function destroyTag(Request $request)
    {
        $request->validate(['index' => 'required|integer|min:0']);
        $business = auth()->user()->business;
        $tags = $business->tags ?? [];
        array_splice($tags, $request->index, 1);
        $business->update(['tags' => array_values($tags)]);
        return back()->with('success', 'Tag deleted.');
    }

    // =============================================
    // PRINT TEMPLATES
    // =============================================
    public function printTemplates()
    {
        $business = auth()->user()->business;
        $printSettings = $business->print_settings ?? [];
        return view('settings.print-templates', compact('business', 'printSettings'));
    }

    public function updatePrintTemplates(Request $request)
    {
        $business = auth()->user()->business;
        $business->update([
            'print_settings' => [
                'invoice_footer'   => $request->invoice_footer ?? '',
                'invoice_paper'    => $request->invoice_paper ?? 'a4',
                'receipt_width'    => $request->receipt_width ?? '80mm',
                'show_logo'        => $request->boolean('show_logo', true),
                'show_barcode'     => $request->boolean('show_barcode', false),
                'show_tax_number'  => $request->boolean('show_tax_number', false),
            ],
        ]);
        return back()->with('success', 'Print settings saved!');
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

    // =============================================
    // ACCOUNT CATEGORIES
    // =============================================
    public function accountCategories()
    {
        $bizId    = auth()->user()->business_id;
        $expense  = AccountCategory::where('business_id', $bizId)->where('type', 'expense')->orderBy('name')->get();
        $income   = AccountCategory::where('business_id', $bizId)->where('type', 'income')->orderBy('name')->get();
        return view('settings.account-categories', compact('expense', 'income'));
    }

    public function storeAccountCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:expense,income',
        ]);

        $bizId = auth()->user()->business_id;

        // Prevent duplicates
        $exists = AccountCategory::where('business_id', $bizId)
            ->where('type', $request->type)
            ->whereRaw('LOWER(name) = ?', [strtolower($request->name)])
            ->exists();

        if ($exists) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Category already exists'], 422);
            }
            return back()->withErrors(['name' => 'This category already exists.'])->withInput();
        }

        $cat = AccountCategory::create([
            'business_id' => $bizId,
            'name'        => ucfirst(trim($request->name)),
            'type'        => $request->type,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['id' => $cat->id, 'name' => $cat->name]);
        }

        return back()->with('success', 'Category "' . $cat->name . '" created.');
    }

    public function destroyAccountCategory(AccountCategory $cat)
    {
        // Ensure it belongs to this business
        abort_if($cat->business_id !== auth()->user()->business_id, 403);

        // Check if in use
        $inUse = $cat->transactions()->count();
        if ($inUse) {
            return back()->withErrors(['delete' => "Cannot delete — {$inUse} transaction(s) use this category."]);
        }

        $cat->delete();
        return back()->with('success', 'Category deleted.');
    }
}
