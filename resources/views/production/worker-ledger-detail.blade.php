@extends('layouts.app')
@section('title', $employee->name . ' Ledger')
@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('production.worker.ledger', ['month'=>$month]) }}"
           class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-slate-300">← All Workers</a>
        <span class="text-gray-300 dark:text-slate-600">/</span>
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ $employee->name }}</h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                {{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }} Ledger
            </p>
        </div>
    </div>
    {{-- Export --}}
    <div class="flex items-center gap-2" x-data="{ open: false }" @click.outside="open = false">
        <button @click="open = !open"
                class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-300 transition-colors shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export
            <svg class="w-3 h-3 transition-transform" :class="open?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-cloak x-transition
             class="absolute right-4 mt-24 z-30 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-600 shadow-lg p-1 min-w-36">
            <a href="{{ route('production.worker.ledger.export', [$employee, 'month'=>$month, 'format'=>'csv']) }}"
               class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
            <a href="{{ route('production.worker.ledger.export', [$employee, 'month'=>$month, 'format'=>'pdf']) }}"
               class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
        </div>
    </div>
</div>

{{-- Month Picker --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4 flex flex-wrap items-center gap-2">
    <label class="text-xs text-gray-500 dark:text-slate-400 font-medium">Month:</label>
    <form method="GET" class="flex items-center gap-2">
        <select name="month" onchange="this.form.submit()"
                class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
            @foreach($months as $m)
            <option value="{{ $m['value'] }}" {{ $month === $m['value'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- Summary Cards (Excel header row style) --}}
<div class="grid grid-cols-3 gap-3 mb-5">
    {{-- Carry-over --}}
    @if($carryOver != 0)
    <div class="col-span-3 flex items-center gap-2 px-4 py-2.5 rounded-xl border {{ $carryOver > 0 ? 'bg-purple-50 dark:bg-purple-900/20 border-purple-100 dark:border-purple-900/40' : 'bg-orange-50 dark:bg-orange-900/20 border-orange-100 dark:border-orange-900/40' }}">
        <svg class="w-4 h-4 {{ $carryOver > 0 ? 'text-purple-500' : 'text-orange-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="text-xs font-medium {{ $carryOver > 0 ? 'text-purple-700 dark:text-purple-300' : 'text-orange-700 dark:text-orange-300' }}">
            @if($carryOver > 0)
                Previous months overpay carried forward: <strong>৳{{ number_format($carryOver) }}</strong>
            @else
                Previous months due carried forward: <strong>৳{{ number_format(abs($carryOver)) }}</strong>
            @endif
        </span>
    </div>
    @endif

    <div class="bg-primary-50 dark:bg-primary-900/20 rounded-xl p-4 border border-primary-100 dark:border-primary-900/40">
        <p class="text-xs text-primary-600 dark:text-primary-400 font-medium mb-1">
            {{ $monthSalary > 0 ? 'Earned This Month' : 'P.Bill (This Month)' }}
        </p>
        <p class="text-2xl font-bold text-primary-700 dark:text-primary-300">৳{{ number_format($monthEarned) }}</p>
        <p class="text-xs text-primary-500 mt-1">
            @if($monthSalary > 0)
                ৳{{ number_format($monthSalary) }} salary{{ $entries->sum('amount') > 0 ? ' + ৳'.number_format($entries->sum('amount')).' production' : '' }}
            @else
                {{ $entries->sum('quantity') }} pieces
            @endif
        </p>
    </div>
    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-100 dark:border-green-900/40">
        <p class="text-xs text-green-600 dark:text-green-400 font-medium mb-1">Paid (This Month)</p>
        <p class="text-2xl font-bold text-green-700 dark:text-green-300">৳{{ number_format($monthPaid) }}</p>
        <p class="text-xs text-green-500 mt-1">{{ $payments->count() }} payments</p>
    </div>
    @php $isOverpay = $runningDue < 0; @endphp
    <div class="bg-{{ $isOverpay ? 'purple' : 'red' }}-50 dark:bg-{{ $isOverpay ? 'purple' : 'red' }}-900/20 rounded-xl p-4 border border-{{ $isOverpay ? 'purple' : 'red' }}-100 dark:border-{{ $isOverpay ? 'purple' : 'red' }}-900/40">
        <p class="text-xs text-{{ $isOverpay ? 'purple' : 'red' }}-600 dark:text-{{ $isOverpay ? 'purple' : 'red' }}-400 font-medium mb-1">
            {{ $isOverpay ? 'Over Pay' : 'Due' }} (All-time)
        </p>
        <p class="text-2xl font-bold text-{{ $isOverpay ? 'purple' : 'red' }}-700 dark:text-{{ $isOverpay ? 'purple' : 'red' }}-300">
            ৳{{ number_format(abs($runningDue)) }}
        </p>
        <p class="text-xs text-{{ $isOverpay ? 'purple' : 'red' }}-500 mt-1">
            balance through selected month
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- LEFT: Production Entries / Salary Info --}}
    <div class="lg:col-span-2 space-y-4">

        @if($monthSalary > 0 && $entries->isEmpty())
        {{-- Salary employee: show attendance summary for this month --}}
        @php
            $attRecs = \App\Models\Attendance::where('employee_id', $employee->id)
                ->whereBetween('attendance_date', [$from, \Carbon\Carbon::parse($to)->endOfDay()])
                ->orderBy('attendance_date')->get();
        @endphp
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Attendance — {{ \Carbon\Carbon::parse($from)->format('F Y') }}</h3>
                <div class="flex gap-2 text-xs">
                    <span class="px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">Present: {{ $attRecs->whereIn('status',['present','late'])->count() }}</span>
                    <span class="px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">Absent: {{ $attRecs->where('status','absent')->count() }}</span>
                </div>
            </div>
            @if($attRecs->isNotEmpty())
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="text-left px-4 py-2 font-medium text-gray-500 dark:text-slate-400">Date</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-500 dark:text-slate-400">Status</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-500 dark:text-slate-400">Check In / Out</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @foreach($attRecs as $rec)
                @php $sc=['present'=>'bg-green-100 text-green-700','late'=>'bg-orange-100 text-orange-700','absent'=>'bg-red-100 text-red-700','half_day'=>'bg-yellow-100 text-yellow-700','holiday'=>'bg-blue-100 text-blue-700']; @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                    <td class="px-4 py-2 text-gray-600 dark:text-slate-300">{{ \Carbon\Carbon::parse($rec->attendance_date)->format('d M, D') }}</td>
                    <td class="px-4 py-2"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sc[$rec->status] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst(str_replace('_',' ',$rec->status)) }}</span></td>
                    <td class="px-4 py-2 text-gray-400">{{ $rec->check_in ? \Carbon\Carbon::parse($rec->check_in)->format('h:i A') : '—' }}{{ $rec->check_out ? ' → '.\Carbon\Carbon::parse($rec->check_out)->format('h:i A') : '' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @else
            <div class="py-8 text-center text-xs text-gray-400 dark:text-slate-500">No attendance records for this month.</div>
            @endif
        </div>
        @else

        {{-- Product Breakdown (summary) --}}
        @if($productBreakdown->isNotEmpty())
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Product Summary</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Product</th>
                        <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Color</th>
                        <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Size</th>
                        <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Rate</th>
                        <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Qty</th>
                        <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                    @foreach($productBreakdown as $prod)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-white">{{ $prod['product_name'] }}</td>
                        <td class="px-4 py-2.5 text-center">
                            @if($prod['color'] !== '—')
                            <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300">{{ $prod['color'] }}</span>
                            @else <span class="text-gray-400 text-xs">—</span> @endif
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            @if($prod['size'] !== '—')
                            <span class="px-2 py-0.5 text-xs rounded-full bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400">{{ $prod['size'] }}</span>
                            @else <span class="text-gray-400 text-xs">—</span> @endif
                        </td>
                        <td class="px-4 py-2.5 text-center text-xs text-gray-500 dark:text-slate-400">৳{{ number_format($prod['rate'], 0) }}</td>
                        <td class="px-4 py-2.5 text-center font-bold text-gray-900 dark:text-white">{{ $prod['qty'] }}</td>
                        <td class="px-4 py-2.5 text-right font-bold text-primary-600 dark:text-primary-400">৳{{ number_format($prod['amount']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-primary-50 dark:bg-primary-900/10 border-t-2 border-primary-100 dark:border-primary-900/30">
                    <tr>
                        <td colspan="4" class="px-4 py-2.5 font-bold text-gray-900 dark:text-white text-xs">Total</td>
                        <td class="px-4 py-2.5 text-center font-bold text-gray-900 dark:text-white">{{ $entries->sum('quantity') }}</td>
                        <td class="px-4 py-2.5 text-right font-bold text-primary-700 dark:text-primary-400">৳{{ number_format($entries->sum('amount')) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif

        {{-- Daily Entries (like Excel ledger) --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Daily Production Entries</h3>
                <span class="text-xs text-gray-400 dark:text-slate-500">{{ $entries->count() }} entries</span>
            </div>
            @if($entries->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Date</th>
                            <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Product</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden sm:table-cell">Color</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden sm:table-cell">Size</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Qty</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400 hidden sm:table-cell">Rate</th>
                            <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">T-Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                        @foreach($entries as $entry)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-slate-400 whitespace-nowrap">
                                {{ $entry->entry_date->format('d/M/y') }}
                            </td>
                            <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-white text-sm">
                                {{ $entry->productionRate?->product_name ?? 'Manual' }}
                                @if($entry->notes) <span class="text-xs text-gray-400">({{ $entry->notes }})</span> @endif
                            </td>
                            <td class="px-4 py-2.5 text-center hidden sm:table-cell">
                                <span class="text-xs text-gray-500 dark:text-slate-400">{{ $entry->productionRate?->color ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-center hidden sm:table-cell">
                                <span class="text-xs text-gray-500 dark:text-slate-400">{{ $entry->productionRate?->size ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-center font-bold text-gray-900 dark:text-white">{{ $entry->quantity }}</td>
                            <td class="px-4 py-2.5 text-center text-xs text-gray-400 dark:text-slate-500 hidden sm:table-cell">
                                ৳{{ number_format($entry->productionRate?->rate ?? 0, 0) }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-bold text-primary-600 dark:text-primary-400">
                                ৳{{ number_format($entry->amount) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-slate-700/50 border-t-2 border-gray-200 dark:border-slate-600">
                        <tr>
                            <td colspan="4" class="px-4 py-2.5 font-bold text-gray-900 dark:text-white text-xs">P.Bill</td>
                            <td class="px-4 py-2.5 text-center font-bold text-gray-900 dark:text-white">{{ $entries->sum('quantity') }}</td>
                            <td class="hidden sm:table-cell"></td>
                            <td class="px-4 py-2.5 text-right font-bold text-primary-700 dark:text-primary-400 text-base">৳{{ number_format($entries->sum('amount')) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <div class="px-4 py-10 text-center text-sm text-gray-400 dark:text-slate-500">No production entries this month</div>
            @endif
        </div>

        @endif {{-- end salary/production branch --}}
    </div>

    {{-- RIGHT: Payments --}}
    <div class="space-y-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Payments</h3>
                <span class="text-xs text-gray-400 dark:text-slate-500">{{ $payments->count() }}</span>
            </div>
            @if($payments->isNotEmpty())
            <div class="divide-y divide-gray-50 dark:divide-slate-700">
                @foreach($payments as $pay)
                <div class="px-4 py-2.5 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-slate-400">{{ $pay->payment_date->format('d/M/y') }}</p>
                        <p class="text-xs text-gray-400 dark:text-slate-500">
                            {{ ucfirst($pay->type) }}
                            @if($pay->payment_method) · {{ ucfirst($pay->payment_method) }} @endif
                        </p>
                        @if($pay->notes)
                        <p class="text-xs text-gray-400 italic">{{ $pay->notes }}</p>
                        @endif
                    </div>
                    <span class="font-bold text-green-600 dark:text-green-400 text-sm">৳{{ number_format($pay->amount) }}</span>
                </div>
                @endforeach
            </div>
            <div class="px-4 py-2.5 bg-green-50 dark:bg-green-900/10 flex items-center justify-between border-t-2 border-green-100 dark:border-green-900/30">
                <span class="text-xs font-bold text-gray-700 dark:text-slate-300">Salary / advance paid</span>
                <span class="font-bold text-green-700 dark:text-green-400">৳{{ number_format($monthPaid) }}</span>
            </div>
            @else
            <div class="px-4 py-6 text-center text-xs text-gray-400 dark:text-slate-500">No payments this month</div>
            @endif
        </div>

        {{-- Balance Summary Card (like Excel bottom row) --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 space-y-3">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Summary</h3>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-slate-400">Earned</span>
                <span class="font-bold text-primary-600 dark:text-primary-400">৳{{ number_format($monthEarned) }}</span>
            </div>
            @if($carryOver != 0)
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-slate-400">{{ $carryOver > 0 ? 'Prev. Overpay (-)' : 'Prev. Due (+)' }}</span>
                <span class="font-bold {{ $carryOver > 0 ? 'text-purple-600 dark:text-purple-400' : 'text-orange-600 dark:text-orange-400' }}">
                    {{ $carryOver > 0 ? '−' : '+' }}৳{{ number_format(abs($carryOver)) }}
                </span>
            </div>
            @endif
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-slate-400">Paid</span>
                <span class="font-bold text-green-600 dark:text-green-400">৳{{ number_format($monthPaid) }}</span>
            </div>
            @if($monthBonus > 0)
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-slate-400">Bonus paid (separate)</span>
                <span>৳{{ number_format($monthBonus) }}</span>
            </div>
            @endif
            @if($monthDeduction > 0)
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-slate-400">Deductions (-)</span>
                <span>৳{{ number_format($monthDeduction) }}</span>
            </div>
            @endif
            <div class="border-t border-gray-100 dark:border-slate-700 pt-3 flex justify-between">
                <span class="font-bold text-gray-900 dark:text-white text-sm">{{ $isOverpay ? 'Over Pay' : 'Due' }}</span>
                <span class="font-black text-lg {{ $isOverpay ? 'text-purple-600 dark:text-purple-400' : 'text-red-600 dark:text-red-400' }}">
                    ৳{{ number_format(abs($runningDue)) }}
                </span>
            </div>
        </div>

        {{-- Quick Pay button --}}
        <a href="{{ route('production.payroll', ['month'=>$month]) }}"
           class="block w-full text-center py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition-colors">
            💰 Add Payment
        </a>
    </div>
</div>

@endsection
