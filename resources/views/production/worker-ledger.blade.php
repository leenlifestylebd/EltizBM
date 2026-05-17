@extends('layouts.app')
@section('title', 'Worker Ledger')
@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Worker Ledger</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
            {{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }} — P.Bill vs Paid vs Due
        </p>
    </div>
</div>

{{-- Month Picker --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <label class="text-xs text-gray-500 dark:text-slate-400 font-medium">Month:</label>
        <select name="month" onchange="this.form.submit()"
                class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
            @foreach($months as $m)
            <option value="{{ $m['value'] }}" {{ $month === $m['value'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-900/40">
        <p class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">Total P.Bill</p>
        <p class="text-xl font-bold text-blue-700 dark:text-blue-300">৳{{ number_format($totals['earned']) }}</p>
        <p class="text-xs text-blue-500 mt-1">{{ number_format($totals['qty']) }} pcs</p>
    </div>
    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-100 dark:border-green-900/40">
        <p class="text-xs text-green-600 dark:text-green-400 font-medium mb-1">Total Paid</p>
        <p class="text-xl font-bold text-green-700 dark:text-green-300">৳{{ number_format($totals['paid']) }}</p>
    </div>
    <div class="bg-{{ $totals['balance'] > 0 ? 'red' : 'purple' }}-50 dark:bg-{{ $totals['balance'] > 0 ? 'red' : 'purple' }}-900/20 rounded-xl p-4 border border-{{ $totals['balance'] > 0 ? 'red' : 'purple' }}-100 dark:border-{{ $totals['balance'] > 0 ? 'red' : 'purple' }}-900/40">
        <p class="text-xs text-{{ $totals['balance'] > 0 ? 'red' : 'purple' }}-600 dark:text-{{ $totals['balance'] > 0 ? 'red' : 'purple' }}-400 font-medium mb-1">
            {{ $totals['balance'] > 0 ? 'Total Due' : 'Total Overpay' }}
        </p>
        <p class="text-xl font-bold text-{{ $totals['balance'] > 0 ? 'red' : 'purple' }}-700 dark:text-{{ $totals['balance'] > 0 ? 'red' : 'purple' }}-300">
            ৳{{ number_format(abs($totals['balance'])) }}
        </p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">Workers</p>
        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $workers->count() }}</p>
        <p class="text-xs text-gray-400 mt-1">active this month</p>
    </div>
</div>

{{-- Worker Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-700/50">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400">Worker</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400">Pieces</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400">P.Bill</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400">Paid</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400">Due / Overpay</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400 hidden sm:table-cell">All-time Due</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($workers as $w)
                @php
                    $emp = $w['employee'];
                    $isDue = $w['month_balance'] > 0;
                    $isOver = $w['month_balance'] < 0;
                    $cumDue = $w['cumulative_balance'];
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                <span class="text-xs font-bold text-blue-600 dark:text-blue-400">{{ strtoupper(substr($emp->name,0,1)) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $emp->name }}</p>
                                <p class="text-xs text-gray-400 dark:text-slate-500">{{ $emp->designation ?? $emp->type ?? 'Worker' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-700 dark:text-slate-300 font-medium">
                        {{ number_format($w['month_qty']) }}
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600 dark:text-blue-400">
                        ৳{{ number_format($w['month_earned']) }}
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-green-600 dark:text-green-400">
                        ৳{{ number_format($w['month_paid']) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($w['month_balance'] == 0)
                        <span class="px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 rounded-full">Clear</span>
                        @elseif($isDue)
                        <span class="font-bold text-red-600 dark:text-red-400">৳{{ number_format($w['month_balance']) }}</span>
                        <span class="ml-1 text-xs text-red-400">due</span>
                        @else
                        <span class="font-bold text-purple-600 dark:text-purple-400">৳{{ number_format(abs($w['month_balance'])) }}</span>
                        <span class="ml-1 text-xs text-purple-400">overpay</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right hidden sm:table-cell">
                        @if($cumDue > 0)
                        <span class="text-xs text-red-500 font-medium">৳{{ number_format($cumDue) }} due</span>
                        @elseif($cumDue < 0)
                        <span class="text-xs text-purple-500 font-medium">৳{{ number_format(abs($cumDue)) }} advance</span>
                        @else
                        <span class="text-xs text-green-500">✓ Clear</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('production.worker.ledger.detail', [$emp, 'month'=>$month]) }}"
                           class="px-2.5 py-1 text-xs font-semibold bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition-colors">
                            Ledger →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">
                        No workers with entries this month
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($workers->isNotEmpty())
            <tfoot class="bg-gray-50 dark:bg-slate-700/50 border-t-2 border-gray-200 dark:border-slate-600">
                <tr>
                    <td class="px-4 py-3 font-bold text-gray-900 dark:text-white text-sm">Total</td>
                    <td class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white">{{ number_format($totals['qty']) }}</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-700 dark:text-blue-400">৳{{ number_format($totals['earned']) }}</td>
                    <td class="px-4 py-3 text-right font-bold text-green-700 dark:text-green-400">৳{{ number_format($totals['paid']) }}</td>
                    <td class="px-4 py-3 text-right font-bold {{ $totals['balance'] > 0 ? 'text-red-700 dark:text-red-400' : 'text-purple-700 dark:text-purple-400' }}">
                        ৳{{ number_format(abs($totals['balance'])) }}
                    </td>
                    <td class="hidden sm:table-cell"></td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@endsection
