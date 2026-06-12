@extends('layouts.app')
@section('title', $employee->name)

@section('content')

<div class="max-w-4xl mx-auto" x-data="{ tab: 'overview' }">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('employees.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div class="flex-1">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ $employee->name }}</h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $employee->designation ?? 'Employee' }} @if($employee->department)· {{ $employee->department }}@endif</p>
        </div>
        <a href="{{ route('employees.edit', $employee) }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit
        </a>
    </div>

    {{-- Profile Card --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 mb-4">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-16 h-16 rounded-full flex items-center justify-center text-white font-bold text-xl flex-shrink-0
                {{ ['bg-primary-500','bg-purple-500','bg-green-500','bg-orange-500','bg-pink-500','bg-teal-500'][crc32($employee->name) % 6] }}">
                {{ strtoupper(substr($employee->name, 0, 2)) }}
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $employee->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-slate-400">{{ $employee->designation ?? '—' }}</p>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-1
                    {{ $employee->status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-slate-400' }}">
                    {{ ucfirst($employee->status) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @foreach([
                ['label'=>'Phone',       'value'=> $employee->phone ?? '—'],
                ['label'=>'Email',       'value'=> $employee->email ?? '—'],
                ['label'=>'Salary',      'value'=> '৳'.number_format($employee->salary).'/mo'],
                ['label'=>'Joined',      'value'=> $employee->joining_date?->format('d M Y') ?? '—'],
            ] as $info)
            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2.5">
                <p class="text-xs text-gray-400 dark:text-slate-500">{{ $info['label'] }}</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5 truncate">{{ $info['value'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        @if($employee->type === 'salary' && (float)$employee->salary > 0)
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-indigo-100 dark:border-indigo-900/30 p-3 text-center">
            <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400">৳{{ number_format($employee->salary, 0) }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400">Monthly Salary</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-green-100 dark:border-green-900/30 p-3 text-center">
            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $monthAttendance }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400">Days Present</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-blue-100 dark:border-blue-900/30 p-3 text-center">
            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">৳{{ number_format($monthTotalPaid, 0) }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400">Paid This Month</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-orange-100 dark:border-orange-900/30 p-3 text-center">
            <p class="text-xl font-bold {{ $monthNetPayable > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400' }}">
                ৳{{ number_format(abs($monthNetPayable), 0) }}
            </p>
            <p class="text-xs text-gray-500 dark:text-slate-400">{{ $monthNetPayable < 0 ? 'Overpaid' : 'Net Payable' }}</p>
        </div>
        @else
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 text-center">
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $employee->todayProduction() }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400">Today</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-primary-100 dark:border-primary-900/30 p-3 text-center">
            <p class="text-xl font-bold text-primary-600 dark:text-primary-400">{{ $monthProduction }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400">This Month</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-green-100 dark:border-green-900/30 p-3 text-center">
            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $monthAttendance }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400">Days Present</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-purple-100 dark:border-purple-900/30 p-3 text-center">
            <p class="text-xl font-bold text-purple-600 dark:text-purple-400">৳{{ number_format($monthEarning, 0) }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400">Month Earning</p>
        </div>
        @endif
    </div>

    {{-- Tabs --}}
    @php
        $tabs = $employee->type === 'salary' && (float)$employee->salary > 0
            ? [['key'=>'overview','label'=>'Overview'],['key'=>'payments','label'=>'Payments'],['key'=>'attendance','label'=>'Attendance']]
            : [['key'=>'overview','label'=>'Overview'],['key'=>'production','label'=>'Production'],['key'=>'attendance','label'=>'Attendance']];
    @endphp
    <div class="flex gap-1 mb-4 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl">
        @foreach($tabs as $t)
        <button @click="tab = '{{ $t['key'] }}'"
                class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors"
                :class="tab === '{{ $t['key'] }}' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300'">
            {{ $t['label'] }}
        </button>
        @endforeach
    </div>

    {{-- Tab: Overview --}}
    <div x-show="tab === 'overview'">
        @if($employee->type === 'salary' && (float)$employee->salary > 0)
        {{-- Salary employee overview --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">This Month Summary</h3>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-slate-400">Monthly Salary</span>
                    <span class="font-semibold text-gray-900 dark:text-white">৳{{ number_format($employee->salary, 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-slate-400">Paid This Month</span>
                    <span class="font-semibold text-green-600 dark:text-green-400">- ৳{{ number_format($monthTotalPaid, 0) }}</span>
                </div>
                @if($monthDeductions > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-slate-400">Deductions</span>
                    <span class="font-semibold text-red-500">+ ৳{{ number_format($monthDeductions, 0) }}</span>
                </div>
                @endif
                <div class="border-t border-gray-100 dark:border-slate-700 pt-2 flex justify-between text-sm font-bold">
                    <span class="text-gray-900 dark:text-white">Net Payable</span>
                    <span class="{{ $monthNetPayable > 0 ? 'text-orange-600' : 'text-green-600' }}">
                        ৳{{ number_format(abs($monthNetPayable), 0) }}
                        @if($monthNetPayable < 0) <span class="text-xs font-normal">(Overpaid)</span> @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Recent Payments --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Payments</h3>
            </div>
            @forelse($recentPayments as $pay)
            @php $typeColors=['salary'=>'bg-indigo-100 text-indigo-700','advance'=>'bg-orange-100 text-orange-700','bonus'=>'bg-green-100 text-green-700','deduction'=>'bg-red-100 text-red-700']; @endphp
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 dark:border-slate-700 last:border-0">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$pay->type] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($pay->type) }}</span>
                        <span class="text-xs text-gray-400 dark:text-slate-500">{{ $pay->payment_date->format('d M Y') }}</span>
                    </div>
                    @if($pay->notes)<p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $pay->notes }}</p>@endif
                </div>
                <p class="text-sm font-bold {{ $pay->type === 'deduction' ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                    {{ $pay->type === 'deduction' ? '-' : '' }}৳{{ number_format($pay->amount, 0) }}
                </p>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-sm text-gray-400 dark:text-slate-500">No payments recorded yet</div>
            @endforelse
        </div>

        @else
        {{-- Production employee overview --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Last 7 Days Production</h3>
            <div class="flex items-end gap-1 h-24">
                @foreach($last7Days as $day)
                <div class="flex-1 flex flex-col items-center gap-1">
                    <span class="text-xs text-gray-400 dark:text-slate-500">{{ $day['qty'] }}</span>
                    <div class="w-full rounded-t-sm bg-primary-500 dark:bg-primary-600 transition-all"
                         style="height: {{ $day['qty'] > 0 ? max(4, ($day['qty'] / max(collect($last7Days)->max('qty'), 1)) * 80) : 4 }}px; min-height:4px;"></div>
                    <span class="text-xs text-gray-400 dark:text-slate-500">{{ $day['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Entries</h3>
                <a href="{{ route('production.worker.ledger.detail', $employee) }}" class="text-xs text-primary-600 hover:underline">Full Ledger →</a>
            </div>
            @forelse($recentEntries as $entry)
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 dark:border-slate-700 last:border-0">
                <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $entry->productionRate?->product_name ?? 'Manual Entry' }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                        {{ $entry->entry_date->format('d M Y') }}
                        @if($entry->productionRate?->color) · {{ $entry->productionRate->color }}@endif
                        @if($entry->productionRate?->size) · {{ $entry->productionRate->size }}@endif
                    </p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-sm font-bold text-green-600 dark:text-green-400">৳{{ number_format($entry->amount, 0) }}</p>
                    <p class="text-xs text-gray-400">{{ $entry->quantity }} pcs</p>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-sm text-gray-400 dark:text-slate-500">No production entries yet</div>
            @endforelse
        </div>
        @endif
    </div>

    {{-- Tab: Payments (salary employees only) --}}
    @if($employee->type === 'salary' && (float)$employee->salary > 0)
    <div x-show="tab === 'payments'" x-cloak>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Payment History</h3>
                <a href="{{ route('production.worker.ledger.detail', $employee) }}"
                   class="text-xs text-primary-600 hover:underline">Full Ledger →</a>
            </div>
            @php
                $allPayments = \App\Models\EmployeePayment::where('employee_id', $employee->id)
                    ->orderByDesc('payment_date')->paginate(20);
                $typeColors = ['salary'=>'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400','advance'=>'bg-orange-100 text-orange-700','bonus'=>'bg-green-100 text-green-700','deduction'=>'bg-red-100 text-red-700'];
            @endphp
            @if($allPayments->count())
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Date</th>
                        <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Type</th>
                        <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Amount</th>
                        <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                    @foreach($allPayments as $pay)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-2.5 text-xs text-gray-600 dark:text-slate-300 whitespace-nowrap">{{ $pay->payment_date->format('d M Y') }}</td>
                        <td class="px-4 py-2.5"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$pay->type] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($pay->type) }}</span></td>
                        <td class="px-4 py-2.5 text-right font-semibold {{ $pay->type === 'deduction' ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                            {{ $pay->type === 'deduction' ? '-' : '' }}৳{{ number_format($pay->amount, 0) }}
                        </td>
                        <td class="px-4 py-2.5 text-xs text-gray-400 dark:text-slate-500 truncate max-w-[140px]">{{ $pay->notes ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($allPayments->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $allPayments->links() }}</div>
            @endif
            @else
            <div class="px-4 py-10 text-center text-sm text-gray-400 dark:text-slate-500">No payments recorded yet.</div>
            @endif
        </div>
    </div>
    @endif

    {{-- Tab: Production (production employees only) --}}
    <div x-show="tab === 'production'" x-cloak>
        {{-- Quick actions --}}
        <div class="flex gap-2 mb-4">
            <a href="{{ route('production.worker.dashboard', ['employee_id' => $employee->id]) }}"
               class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 text-xs font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-xl hover:bg-primary-100 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Worker Dashboard
            </a>
            <a href="{{ route('production.worker.ledger.detail', $employee) }}"
               class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 text-xs font-semibold text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl hover:bg-gray-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Ledger
            </a>
            <a href="{{ route('production.tasks.create') }}?employee_id={{ $employee->id }}"
               class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 text-xs font-semibold text-white bg-primary-600 rounded-xl hover:bg-primary-700 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Assign Task
            </a>
        </div>

        {{-- Production Entries Table --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Production Entries</h3>
                <span class="text-xs text-gray-400 dark:text-slate-500">{{ $productionEntries->total() }} total</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-slate-700/50">
                            <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Date</th>
                            <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Product</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Qty</th>
                            <th class="text-right px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                        @forelse($productionEntries as $entry)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-4 py-2.5 text-xs text-gray-600 dark:text-slate-300 whitespace-nowrap">{{ $entry->entry_date->format('d M Y') }}</td>
                            <td class="px-4 py-2.5">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $entry->productionRate?->product_name ?? 'Manual' }}</p>
                                @if($entry->productionRate?->color || $entry->productionRate?->size)
                                <p class="text-xs text-gray-400">{{ implode(' · ', array_filter([$entry->productionRate->color, $entry->productionRate->size])) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-center font-bold text-gray-900 dark:text-white">{{ $entry->quantity }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-primary-600 dark:text-primary-400">৳{{ number_format($entry->amount, 0) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-slate-500">No production entries yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($productionEntries->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $productionEntries->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Tab: Attendance --}}
    <div x-show="tab === 'attendance'" x-cloak>

        {{-- Mark Attendance --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Mark Attendance</h3>
            <form method="POST" action="{{ route('employees.attendance.store', $employee) }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Date <span class="text-red-500">*</span></label>
                        <input type="date" name="attendance_date" required value="{{ today()->format('Y-m-d') }}"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Status <span class="text-red-500">*</span></label>
                        <select name="status" required class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="present">✅ Present</option>
                            <option value="absent">❌ Absent</option>
                            <option value="late">⏰ Late</option>
                            <option value="half_day">½ Half Day</option>
                            <option value="holiday">🏖️ Holiday</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Check In</label>
                            <input type="time" name="check_in"
                                   class="w-full px-2 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Check Out</label>
                            <input type="time" name="check_out"
                                   class="w-full px-2 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>
                </div>
                <button type="submit"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                    Save Attendance
                </button>
            </form>
        </div>

        {{-- Calendar View --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ now()->format('F Y') }} Attendance</h3>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-green-500 rounded-full"></span>Present</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-red-400 rounded-full"></span>Absent</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-yellow-400 rounded-full"></span>Late</span>
                </div>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center mb-2">
                @foreach(['Su','Mo','Tu','We','Th','Fr','Sa'] as $d)
                    <div class="text-xs font-medium text-gray-400 dark:text-slate-500 py-1">{{ $d }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7 gap-1">
                {{-- Fill empty days before month starts --}}
                @for($i = 0; $i < $calendarStart; $i++)
                    <div></div>
                @endfor
                @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $dateKey = now()->format('Y-m') . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                        $att = $attendanceMap[$dateKey] ?? null;
                        $isToday = $dateKey === today()->format('Y-m-d');
                        $colors = ['present'=>'bg-green-500','absent'=>'bg-red-400','late'=>'bg-yellow-400','half_day'=>'bg-orange-400','holiday'=>'bg-primary-400'];
                    @endphp
                    <div class="aspect-square rounded-lg flex items-center justify-center text-xs font-medium relative
                        {{ $isToday ? 'ring-2 ring-primary-500' : '' }}
                        {{ $att ? ($colors[$att->status] ?? 'bg-gray-200 dark:bg-slate-600') . ' text-white' : 'bg-gray-50 dark:bg-slate-700/50 text-gray-600 dark:text-slate-300' }}"
                         title="{{ $att ? ucfirst($att->status) : '' }}">
                        {{ $day }}
                    </div>
                @endfor
            </div>
        </div>

        {{-- Attendance Table --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Attendance Records</h3>
            </div>
            <div class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($attendanceRecords as $record)
                <div class="flex items-center gap-3 px-4 py-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                        {{ ['present'=>'bg-green-100 dark:bg-green-900/30','absent'=>'bg-red-100 dark:bg-red-900/30','late'=>'bg-yellow-100 dark:bg-yellow-900/30','half_day'=>'bg-orange-100 dark:bg-orange-900/30','holiday'=>'bg-primary-100 dark:bg-primary-900/30'][$record->status] ?? 'bg-gray-100 dark:bg-slate-700' }}">
                        <span class="text-sm">
                            {{ ['present'=>'✅','absent'=>'❌','late'=>'⏰','half_day'=>'½','holiday'=>'🏖️'][$record->status] ?? '—' }}
                        </span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $record->attendance_date->format('d M Y, l') }}</p>
                        @if($record->check_in || $record->check_out)
                            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                                {{ $record->check_in ? 'In: '.\Carbon\Carbon::parse($record->check_in)->format('H:i') : '' }}
                                {{ $record->check_out ? ' · Out: '.\Carbon\Carbon::parse($record->check_out)->format('H:i') : '' }}
                            </p>
                        @endif
                    </div>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full
                        {{ ['present'=>'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400','absent'=>'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400','late'=>'bg-yellow-100 text-yellow-700','half_day'=>'bg-orange-100 text-orange-700','holiday'=>'bg-primary-100 text-primary-700'][$record->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst(str_replace('_',' ',$record->status)) }}
                    </span>
                </div>
                @empty
                <div class="px-4 py-8 text-center text-sm text-gray-400 dark:text-slate-500">No attendance records</div>
                @endforelse
            </div>
            @if($attendanceRecords->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $attendanceRecords->links() }}</div>
            @endif
        </div>
    </div>

</div>

@endsection
