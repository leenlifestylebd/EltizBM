@extends('layouts.app')
@section('title', 'Salary')

@section('content')

<div class="flex items-center justify-between mb-4">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Salary Management</h2>
</div>

{{-- Month Filter --}}
<div class="flex items-center gap-2 mb-4">
    <form method="GET" class="flex items-center gap-2">
        <input type="month" name="month" value="{{ $month }}"
               class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
        <button type="submit" class="px-3 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm transition-colors">View</button>
    </form>
    <span class="text-sm text-gray-500 dark:text-slate-400">
        Total payable: <strong class="text-gray-900 dark:text-white">৳{{ number_format($employees->sum('salary')) }}</strong>
    </span>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    {{-- Desktop --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Employee</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Salary</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Days Present</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Payable</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Status</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @foreach($employees as $emp)
                @php
                    $daysInMonth    = \Carbon\Carbon::parse($month)->daysInMonth;
                    $daysPresent    = $salaryData[$emp->id]['days_present'] ?? $daysInMonth;
                    $payable        = round(($emp->salary / $daysInMonth) * $daysPresent);
                    $paid           = $salaryData[$emp->id]['paid'] ?? false;
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold
                                {{ ['bg-primary-500','bg-purple-500','bg-green-500','bg-orange-500'][crc32($emp->name) % 4] }}">
                                {{ strtoupper(substr($emp->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $emp->name }}</p>
                                <p class="text-xs text-gray-400 dark:text-slate-500">{{ $emp->designation ?? '—' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">৳{{ number_format($emp->salary) }}</td>
                    <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-slate-300">{{ $daysPresent }} / {{ $daysInMonth }}</td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">৳{{ number_format($payable) }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($paid)
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Paid</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">Pending</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if(!$paid)
                        <form method="POST" action="{{ route('accounts.salary.store') }}">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                            <input type="hidden" name="amount" value="{{ $payable }}">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="title" value="Salary - {{ $emp->name }} ({{ \Carbon\Carbon::parse($month)->format('M Y') }})">
                            <button type="submit"
                                    class="px-3 py-1.5 text-xs font-medium bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                                Pay Salary
                            </button>
                        </form>
                        @else
                            <span class="text-xs text-gray-400 dark:text-slate-500">✓ Done</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden divide-y divide-gray-50 dark:divide-slate-700">
        @foreach($employees as $emp)
        @php
            $daysInMonth = \Carbon\Carbon::parse($month)->daysInMonth;
            $daysPresent = $salaryData[$emp->id]['days_present'] ?? $daysInMonth;
            $payable     = round(($emp->salary / $daysInMonth) * $daysPresent);
            $paid        = $salaryData[$emp->id]['paid'] ?? false;
        @endphp
        <div class="p-4">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <p class="font-semibold text-sm text-gray-900 dark:text-white">{{ $emp->name }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500">{{ $daysPresent }}/{{ $daysInMonth }} days · ৳{{ number_format($payable) }} payable</p>
                </div>
                @if($paid)
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Paid</span>
                @else
                <form method="POST" action="{{ route('accounts.salary.store') }}">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                    <input type="hidden" name="amount" value="{{ $payable }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <input type="hidden" name="title" value="Salary - {{ $emp->name }} ({{ \Carbon\Carbon::parse($month)->format('M Y') }})">
                    <button type="submit" class="px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">Pay</button>
                </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

@endsection


{{-- ============================================================
     FILE: resources/views/accounts/payment-transfer.blade.php
     ============================================================ --}}