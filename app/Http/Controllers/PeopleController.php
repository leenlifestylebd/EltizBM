<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Http\Request;

class PeopleController extends Controller
{
    // ---- Customers ----
    public function customers(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $query = Customer::where('business_id', $bizId)->withCount('orders')->orderByDesc('created_at');
        if ($search = $request->search) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
        }
        $customers = $query->paginate(20);
        return view('people.customers', compact('customers'));
    }

    public function storeCustomer(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
        ]);

        Customer::create([
            'business_id' => auth()->user()->business_id,
            'name'        => $request->name,
            'phone'       => $request->phone,
            'email'       => $request->email,
            'address'     => $request->address,
            'area'        => $request->area,
            'district'    => $request->district,
        ]);

        return back()->with('success', 'Customer added!');
    }

    public function showCustomer(Customer $customer)
    {
        abort_if($customer->business_id !== auth()->user()->business_id, 403);
        $customer->load(['orders' => fn($q) => $q->orderByDesc('created_at')->limit(10)]);
        return view('people.customer-show', compact('customer'));
    }

    public function updateCustomer(Request $request, Customer $customer)
    {
        abort_if($customer->business_id !== auth()->user()->business_id, 403);
        $customer->update($request->only(['name','phone','email','address','area','district']));
        return back()->with('success', 'Customer updated!');
    }

    public function destroyCustomer(Customer $customer)
    {
        abort_if($customer->business_id !== auth()->user()->business_id, 403);
        $customer->delete();
        return back()->with('success', 'Customer deleted.');
    }

    // ---- Vendors ----
    public function vendors(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $query = Vendor::where('business_id', $bizId)->orderByDesc('created_at');
        if ($search = $request->search) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
        }
        $vendors = $query->paginate(20);
        return view('people.vendors', compact('vendors'));
    }

    public function storeVendor(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);

        \App\Models\Vendor::create([
            'business_id' => auth()->user()->business_id,
            'name'        => $request->name,
            'phone'       => $request->phone,
            'email'       => $request->email,
            'address'     => $request->address,
        ]);

        return back()->with('success', 'Vendor added!');
    }

    public function showVendor(\App\Models\Vendor $vendor)
    {
        abort_if($vendor->business_id !== auth()->user()->business_id, 403);
        $purchaseOrders = \App\Models\PurchaseOrder::where('vendor_id', $vendor->id)
            ->orderByDesc('created_at')->paginate(10);
        $totalPurchased = \App\Models\PurchaseOrder::where('vendor_id', $vendor->id)->sum('total');
        return view('people.vendor-show', compact('vendor', 'purchaseOrders', 'totalPurchased'));
    }

    public function updateVendor(Request $request, \App\Models\Vendor $vendor)
    {
        abort_if($vendor->business_id !== auth()->user()->business_id, 403);
        $vendor->update($request->only(['name','phone','email','address']));
        return back()->with('success', 'Vendor updated!');
    }

    public function destroyVendor(\App\Models\Vendor $vendor)
    {
        abort_if($vendor->business_id !== auth()->user()->business_id, 403);
        $vendor->delete();
        return back()->with('success', 'Vendor deleted.');
    }

    // ---- AJAX Search (for order form) ----
    public function apiSearch(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $term  = $request->q;

        $customers = Customer::where('business_id', $bizId)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            })
            ->select('id','name','phone','email','address','area','district','due_amount')
            ->limit(10)
            ->get();

        return response()->json($customers);
    }
}
