<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    // ── Dashboard ─────────────────────────────────────────────
    public function index()
    {
        $stats = [
            'total_businesses' => Business::count(),
            'active_businesses'=> Business::whereNotNull('trial_ends_at')->count(),
            'trial_businesses' => Business::where('trial_ends_at', '>', now())->count(),
            'total_users'      => User::count(),
            'pending_users'    => User::where('is_approved', 0)->count(),
        ];

        $recentBusinesses = Business::withCount('users')
            ->latest()
            ->take(5)
            ->get();

        $pendingUsers = User::with('business')
            ->where('is_approved', 0)
            ->latest()
            ->get();

        return view('super-admin.index', compact('stats', 'recentBusinesses', 'pendingUsers'));
    }

    // ── All Businesses ─────────────────────────────────────────
    public function businesses(Request $request)
    {
        $query = Business::withCount(['users', 'products', 'orders'])
            ->latest();

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $businesses = $query->paginate(20);
        return view('super-admin.businesses', compact('businesses'));
    }

    // ── All Users ──────────────────────────────────────────────
    public function users(Request $request)
    {
        $query = User::with('business')->latest();

        if ($request->status === 'pending') {
            $query->where('is_approved', 0);
        } elseif ($request->status === 'inactive') {
            $query->where('is_active', 0);
        }

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(30);
        return view('super-admin.users', compact('users'));
    }

    // ── Approve User ───────────────────────────────────────────
    public function approveUser(User $user)
    {
        $user->update(['is_approved' => 1, 'is_active' => 1]);
        return back()->with('success', "User '{$user->name}' approved.");
    }

    // ── Reject / Delete User ───────────────────────────────────
    public function rejectUser(User $user)
    {
        $name = $user->name;
        $user->delete();
        return back()->with('success', "User '{$name}' rejected and removed.");
    }

    // ── Toggle Business Active/Inactive ───────────────────────
    public function toggleBusiness(Business $business)
    {
        // If ALL owner users are active → deactivate, else activate
        $owners = $business->users()->where('role', 'owner')->get();
        $allActive = $owners->every(fn($u) => $u->is_active);

        $business->users()->update(['is_active' => $allActive ? 0 : 1]);

        $status = $allActive ? 'deactivated' : 'activated';
        return back()->with('success', "Business '{$business->name}' {$status}.");
    }

    // ── Extend Trial ───────────────────────────────────────────
    public function extendTrial(Request $request, Business $business)
    {
        $days = (int) ($request->days ?? 30);
        $newEnd = ($business->trial_ends_at && $business->trial_ends_at->isFuture())
            ? $business->trial_ends_at->addDays($days)
            : now()->addDays($days);

        $business->update(['trial_ends_at' => $newEnd]);
        return back()->with('success', "Trial extended by {$days} days for '{$business->name}'.");
    }

    // ── Create Business + Owner ────────────────────────────────
    public function storeBusiness(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:100',
            'owner_name'    => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|string|min:8',
            'trial_days'    => 'nullable|integer|min:1|max:365',
        ]);

        DB::transaction(function () use ($request) {
            $business = Business::create([
                'name'          => $request->business_name,
                'slug'          => Str::slug($request->business_name) . '-' . Str::random(5),
                'email'         => $request->email,
                'plan'          => 'basic',
                'trial_ends_at' => now()->addDays((int) ($request->trial_days ?? 14)),
            ]);

            User::create([
                'business_id' => $business->id,
                'name'        => $request->owner_name,
                'email'       => $request->email,
                'password'    => Hash::make($request->password),
                'role'        => 'owner',
                'is_approved' => 1,
                'is_active'   => 1,
            ]);

            // Default warehouse
            \App\Models\Warehouse::create([
                'business_id' => $business->id,
                'name'        => 'Main Warehouse',
                'is_default'  => true,
            ]);

            // Default account categories
            foreach (['Sales', 'Service', 'Other Income'] as $name) {
                \App\Models\AccountCategory::create([
                    'business_id' => $business->id,
                    'name'        => $name,
                    'type'        => 'income',
                ]);
            }
            foreach (['Purchase', 'Salary', 'Rent', 'Utility', 'Marketing', 'Transport', 'Other Expense'] as $name) {
                \App\Models\AccountCategory::create([
                    'business_id' => $business->id,
                    'name'        => $name,
                    'type'        => 'expense',
                ]);
            }
        });

        return back()->with('success', "Business '{$request->business_name}' created successfully!");
    }

    // ── Reset Business Data ────────────────────────────────────
    public function resetData(Request $request, Business $business)
    {
        $request->validate([
            'confirm_name' => 'required|string',
            'modules'      => 'required|array|min:1',
        ]);

        if (trim($request->confirm_name) !== trim($business->name)) {
            return back()->with('error', 'Business name does not match. Reset cancelled.');
        }

        $bizId   = $business->id;
        $modules = $request->modules;
        $deleted = [];

        DB::transaction(function () use ($bizId, $modules, &$deleted) {

            if (in_array('orders', $modules)) {
                DB::table('order_items')->whereIn('order_id',
                    DB::table('orders')->where('business_id', $bizId)->pluck('id')
                )->delete();
                DB::table('orders')->where('business_id', $bizId)->delete();
                $deleted[] = 'Orders';
            }

            if (in_array('products', $modules)) {
                DB::table('product_variants')->whereIn('product_id',
                    DB::table('products')->where('business_id', $bizId)->pluck('id')
                )->delete();
                DB::table('products')->where('business_id', $bizId)->delete();
                $deleted[] = 'Products';
            }

            if (in_array('customers', $modules)) {
                DB::table('customers')->where('business_id', $bizId)->delete();
                $deleted[] = 'Customers';
            }

            if (in_array('transactions', $modules)) {
                DB::table('transactions')->where('business_id', $bizId)->delete();
                $deleted[] = 'Transactions';
            }

            if (in_array('employees', $modules)) {
                DB::table('employee_payments')->whereIn('employee_id',
                    DB::table('employees')->where('business_id', $bizId)->pluck('id')
                )->delete();
                DB::table('employees')->where('business_id', $bizId)->delete();
                $deleted[] = 'Employees';
            }

            if (in_array('production', $modules)) {
                DB::table('production_entries')->where('business_id', $bizId)->delete();
                DB::table('production_tasks')->where('business_id', $bizId)->delete();
                $deleted[] = 'Production';
            }

            if (in_array('inventory', $modules)) {
                DB::table('stock_adjustments')->where('business_id', $bizId)->delete();
                DB::table('purchase_orders')->where('business_id', $bizId)->delete();
                $deleted[] = 'Inventory';
            }

            if (in_array('categories', $modules)) {
                DB::table('categories')->where('business_id', $bizId)->delete();
                DB::table('brands')->where('business_id', $bizId)->delete();
                $deleted[] = 'Categories & Brands';
            }
        });

        $summary = implode(', ', $deleted);
        return back()->with('success', "Reset complete for '{$business->name}'. Cleared: {$summary}.");
    }

    // ── Delete Business (hard) ─────────────────────────────────
    public function destroyBusiness(Business $business)
    {
        $name = $business->name;
        // Delete all users first
        $business->users()->delete();
        $business->delete();
        return redirect()->route('super-admin.businesses')
            ->with('success', "Business '{$name}' permanently deleted.");
    }

    // ── Reset Business Password ────────────────────────────────
    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => 'required|string|min:8']);
        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('success', "Password reset for '{$user->name}'.");
    }
}
