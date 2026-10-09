@extends('layouts.app')
@section('title', 'Supplier Due')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Supplier Due</h2>
    <div class="text-right">
        <p class="text-xs text-gray-500 dark:text-slate-400">Total Outstanding</p>
        <p class="text-lg font-semibold text-red-600 dark:text-red-400">৳{{ number_format($totalDue, 2) }}</p>
    </div>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500 dark:text-slate-400 border-b border-gray-100 dark:border-slate-700">
                    <th class="px-4 py-3 font-medium">Supplier</th>
                    <th class="px-4 py-3 font-medium text-right">Purchase Orders</th>
                    <th class="px-4 py-3 font-medium text-right">Total Ordered</th>
                    <th class="px-4 py-3 font-medium text-right">Outstanding Due</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($vendors as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                    <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $row->vendor?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right text-gray-600 dark:text-slate-300">{{ $row->po_count }}</td>
                    <td class="px-4 py-3 text-right text-gray-600 dark:text-slate-300">৳{{ number_format($row->total_ordered, 2) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-red-600 dark:text-red-400">৳{{ number_format($row->due_amount, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-gray-400 dark:text-slate-500">No supplier dues.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($vendors, 'hasPages') && $vendors->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $vendors->links() }}</div>
    @endif
</div>
@endsection
