@extends('layouts.app')
@section('title', 'Customers')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Customers</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $customers->total() }} total</p>
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
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $customer->name }}</td>
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

@endsection