@extends('layouts.app')
@section('title', 'Customers')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <div class="flex items-center gap-3 mb-1">
            <a href="{{ route('people.customers') }}" class="text-xs font-medium px-3 py-1.5 rounded-lg {{ request()->routeIs('people.customers') ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-500 dark:text-slate-400 hover:text-blue-600' }}">Customers</a>
            <a href="{{ route('people.vendors') }}" class="text-xs font-medium px-3 py-1.5 rounded-lg {{ request()->routeIs('people.vendors') ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-500 dark:text-slate-400 hover:text-blue-600' }}">Vendors</a>
        </div>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $customers->total() }} total customers</p>
    </div>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')"
            class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
        + Add Customer
    </button>
</div>

{{-- Search --}}
<form method="GET" class="flex gap-2 mb-4">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or phone..."
           class="flex-1 px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
    <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors">Search</button>
</form>

{{-- Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">#</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Name</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Phone</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Area</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Total Purchase</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Due</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($customers as $i => $customer)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3 text-xs text-gray-400">{{ ($customers->currentPage()-1)*$customers->perPage()+$i+1 }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('people.customers.show', $customer) }}" class="font-medium text-gray-900 dark:text-white hover:text-blue-600">{{ $customer->name }}</a>
                        @if($customer->email)<p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $customer->email }}</p>@endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-slate-300">{{ $customer->phone ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-slate-400">{{ $customer->area ?? '—' }}</td>
                    <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white">৳{{ number_format($customer->total_purchase) }}</td>
                    <td class="px-4 py-3 text-right font-medium {{ $customer->due_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }}">
                        {{ $customer->due_amount > 0 ? '৳'.number_format($customer->due_amount) : '—' }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">No customers yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $customers->withQueryString()->links() }}</div>
    @endif
</div>

{{-- Add Customer Modal --}}
<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-end sm:items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Add Customer</h3>
            <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('people.customers.store') }}" class="p-5 space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Customer name">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Phone</label>
                    <input type="text" name="phone" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="01XXXXXXXXX">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Address</label>
                <input type="text" name="address" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Area</label>
                    <input type="text" name="area" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">District</label>
                    <input type="text" name="district" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">Add Customer</button>
        </form>
    </div>
</div>

@endsection