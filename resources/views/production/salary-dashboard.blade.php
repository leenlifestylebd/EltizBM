@extends('layouts.app')
@section('title', 'My Dashboard')

@php $isWorker = auth()->user()->role === 'employee'; @endphp

@section('content')
<div class="max-w-2xl mx-auto pb-8">

    @if(session('success'))
    <div class="mb-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl flex items-center gap-2">
        <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <p class="text-xs text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow">
                {{ strtoupper(substr($employee->name, 0, 2)) }}
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">{{ $employee->name }}</h2>
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ $employee->designation ?? 'Staff' }}
                    @if($employee->department) &middot; {{ $employee->department }}@endif
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if(in_array(auth()->user()->role, ['admin', 'owner']) && $allEmployees->count() > 1)
            <select onchange="window.location.href='{{ route('production.worker.dashboard') }}?employee_id='+this.value+'&from={{ $from }}'"
                    class="px-2 py-1.5 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none">
                @foreach($allEmployees as $emp)
                <option value="{{ $emp->id }}" {{ $emp->id === $employee->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
            @endif
            @include('production.partials.notification-bell')
        </div>
    </div>

    {{-- Month Selector --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
        <form method="GET" class="flex gap-2 items-center">
            @if(in_array(auth()->user()->role, ['admin', 'owner']))
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            @endif
            <label class="text-xs text-gray-500 dark:text-slate-400 shrink-0">Month</label>
            <input type="month" name="from" value="{{ \Carbon\Carbon::parse($from)->format('Y-m') }}"
                   class="flex-1 px-2 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <button type="submit" class="px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs rounded-lg font-medium transition-colors">View</button>
        </form>
    </div>

    {{-- Salary Summary Cards --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        {{-- Monthly Salary --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Monthly Salary</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">৳{{ number_format($employee->salary, 0) }}</p>
            <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1 font-medium">Fixed</p>
        </div>

        {{-- Net Payable --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Net Payable</p>
            <p class="text-xl font-bold {{ $netPayable > 0 ? 'text-orange-600 dark:text-orange-400' : ($netPayable < 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white') }}">
                ৳{{ number_format(abs($netPayable), 0) }}
            </p>
            <p class="text-xs mt-1">
                @if($netPayable > 0)
                    <span class="text-orange-500">Due to employee</span>
                @elseif($netPayable < 0)
                    <span class="text-green-600 dark:text-green-400">Overpaid — carries to next month</span>
                @else
                    <span class="text-gray-400">Settled</span>
                @endif
            </p>
        </div>

        {{-- Total Paid --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Paid This Month</p>
            <p class="text-xl font-bold text-green-600 dark:text-green-400">৳{{ number_format($totalPaid, 0) }}</p>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">{{ $payments->whereIn('type', ['salary', 'advance', 'bonus'])->count() }} payment(s)</p>
        </div>

        {{-- Attendance --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Attendance</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $daysPresent }}<span class="text-sm font-normal text-gray-400"> / {{ $daysInMonth }}</span></p>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Days present</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div x-data="{ tab: 'attendance' }">
        <div class="flex gap-1 bg-gray-100 dark:bg-slate-700 rounded-xl p-1 mb-4">
            <button @click="tab='attendance'" :class="tab==='attendance' ? 'bg-white dark:bg-slate-600 shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-slate-400'"
                    class="flex-1 py-2 text-xs font-medium rounded-lg transition-all">Attendance</button>
            <button @click="tab='payments'" :class="tab==='payments' ? 'bg-white dark:bg-slate-600 shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-slate-400'"
                    class="flex-1 py-2 text-xs font-medium rounded-lg transition-all">Payments</button>
            <button @click="tab='calendar'" :class="tab==='calendar' ? 'bg-white dark:bg-slate-600 shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-slate-400'"
                    class="flex-1 py-2 text-xs font-medium rounded-lg transition-all">Calendar</button>
        </div>

        {{-- Attendance Tab --}}
        <div x-show="tab==='attendance'">
            {{-- Summary pills --}}
            <div class="flex gap-2 flex-wrap mb-3">
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                    Present: {{ $daysPresent }}
                </span>
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                    Absent: {{ $daysAbsent }}
                </span>
                @if($daysHalfDay)
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400">
                    Half Day: {{ $daysHalfDay }}
                </span>
                @endif
                @if($daysHoliday)
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">
                    Holiday: {{ $daysHoliday }}
                </span>
                @endif
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400">
                    Not Marked: {{ $daysNotMarked }}
                </span>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                @if($attendance->count())
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-slate-700">
                            <th class="px-4 py-2.5 text-left font-medium text-gray-500 dark:text-slate-400">Date</th>
                            <th class="px-4 py-2.5 text-left font-medium text-gray-500 dark:text-slate-400">Status</th>
                            <th class="px-4 py-2.5 text-left font-medium text-gray-500 dark:text-slate-400">Check In/Out</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                        @foreach($attendance as $record)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-2.5 text-gray-700 dark:text-slate-300">
                                {{ \Carbon\Carbon::parse($record->attendance_date)->format('d M, D') }}
                            </td>
                            <td class="px-4 py-2.5">
                                @php
                                    $statusColors = [
                                        'present'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'absent'   => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        'late'     => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                        'half_day' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'holiday'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                    ];
                                    $cls = $statusColors[$record->status] ?? 'bg-gray-100 text-gray-600';
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $cls }}">
                                    {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-gray-500 dark:text-slate-400">
                                @if($record->check_in || $record->check_out)
                                    {{ $record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('h:i A') : '—' }}
                                    @if($record->check_out) &rarr; {{ \Carbon\Carbon::parse($record->check_out)->format('h:i A') }} @endif
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="py-10 text-center">
                    <p class="text-xs text-gray-400 dark:text-slate-500">No attendance records for this month.</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Payments Tab --}}
        <div x-show="tab==='payments'" style="display:none">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                @if($payments->count())
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-slate-700">
                            <th class="px-4 py-2.5 text-left font-medium text-gray-500 dark:text-slate-400">Date</th>
                            <th class="px-4 py-2.5 text-left font-medium text-gray-500 dark:text-slate-400">Type</th>
                            <th class="px-4 py-2.5 text-right font-medium text-gray-500 dark:text-slate-400">Amount</th>
                            <th class="px-4 py-2.5 text-left font-medium text-gray-500 dark:text-slate-400">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                        @foreach($payments as $pay)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-2.5 text-gray-700 dark:text-slate-300">
                                {{ \Carbon\Carbon::parse($pay->payment_date)->format('d M') }}
                            </td>
                            <td class="px-4 py-2.5">
                                @php
                                    $typeColors = [
                                        'salary'    => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                                        'advance'   => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                        'bonus'     => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'deduction' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    ];
                                    $tc = $typeColors[$pay->type] ?? 'bg-gray-100 text-gray-600';
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $tc }}">
                                    {{ ucfirst($pay->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right font-semibold {{ $pay->type === 'deduction' ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $pay->type === 'deduction' ? '-' : '' }}৳{{ number_format($pay->amount, 0) }}
                            </td>
                            <td class="px-4 py-2.5 text-gray-500 dark:text-slate-400 truncate max-w-[100px]">
                                {{ $pay->notes ?? '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700/50">
                            <td colspan="2" class="px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-slate-300">Total Paid</td>
                            <td class="px-4 py-2.5 text-right text-xs font-bold text-green-700 dark:text-green-400">৳{{ number_format($totalPaid, 0) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                @else
                <div class="py-10 text-center">
                    <p class="text-xs text-gray-400 dark:text-slate-500">No payments recorded for this month.</p>
                </div>
                @endif
            </div>

            {{-- Salary Breakdown --}}
            <div class="mt-3 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <p class="text-xs font-semibold text-gray-700 dark:text-slate-300 mb-3">This Month Summary</p>
                <div class="space-y-2">
                    @if($openingBalance != 0)
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500 dark:text-slate-400">
                            {{ $openingBalance > 0 ? 'Carried In (owed from before)' : 'Carried In (overpaid before)' }}
                        </span>
                        <span class="font-medium {{ $openingBalance > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $openingBalance > 0 ? '+' : '-' }} ৳{{ number_format(abs($openingBalance), 0) }}
                        </span>
                    </div>
                    @endif
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500 dark:text-slate-400">Monthly Salary</span>
                        <span class="font-medium text-gray-900 dark:text-white">৳{{ number_format($employee->salary, 0) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500 dark:text-slate-400">Paid This Month</span>
                        <span class="font-medium text-green-600 dark:text-green-400">- ৳{{ number_format($totalPaid, 0) }}</span>
                    </div>
                    @if($totalDeducted > 0)
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500 dark:text-slate-400">Deductions</span>
                        <span class="font-medium text-red-600 dark:text-red-400">+ ৳{{ number_format($totalDeducted, 0) }}</span>
                    </div>
                    @endif
                    <div class="border-t border-gray-100 dark:border-slate-600 pt-2 flex justify-between text-xs font-bold">
                        <span class="text-gray-900 dark:text-white">Net Payable</span>
                        <span class="{{ $netPayable > 0 ? 'text-orange-600 dark:text-orange-400' : ($netPayable < 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white') }}">
                            ৳{{ number_format(abs($netPayable), 0) }}
                            @if($netPayable < 0) (Overpaid) @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Calendar Tab --}}
        <div x-show="tab==='calendar'" style="display:none">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <p class="text-xs font-semibold text-gray-700 dark:text-slate-300 mb-3">
                    {{ \Carbon\Carbon::parse($monthStart)->format('F Y') }}
                </p>

                {{-- Weekday headers --}}
                <div class="grid grid-cols-7 gap-1 mb-1">
                    @foreach(['Su','Mo','Tu','We','Th','Fr','Sa'] as $day)
                    <div class="text-center text-xs font-medium text-gray-400 dark:text-slate-500 py-1">{{ $day }}</div>
                    @endforeach
                </div>

                {{-- Calendar grid --}}
                <div class="grid grid-cols-7 gap-1">
                    {{-- Leading blank cells --}}
                    @for($i = 0; $i < $calendarStart; $i++)
                    <div></div>
                    @endfor

                    @for($d = 1; $d <= $daysInMonth; $d++)
                        @php
                            $dateKey = \Carbon\Carbon::parse($monthStart)->setDay($d)->format('Y-m-d');
                            $rec = $calendarMap->get($dateKey);
                            $dotColor = match($rec?->status ?? '') {
                                'present'  => 'bg-green-500',
                                'late'     => 'bg-orange-400',
                                'absent'   => 'bg-red-500',
                                'half_day' => 'bg-yellow-400',
                                'holiday'  => 'bg-blue-400',
                                default    => 'bg-transparent',
                            };
                            $bgColor = match($rec?->status ?? '') {
                                'present'  => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300',
                                'late'     => 'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300',
                                'absent'   => 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300',
                                'half_day' => 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300',
                                'holiday'  => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300',
                                default    => 'text-gray-400 dark:text-slate-500',
                            };
                            $isToday = $dateKey === today()->toDateString();
                        @endphp
                        <div class="aspect-square flex flex-col items-center justify-center rounded-lg text-xs {{ $bgColor }} {{ $isToday ? 'ring-2 ring-primary-500' : '' }}">
                            <span class="font-medium">{{ $d }}</span>
                            @if($rec) <span class="w-1 h-1 rounded-full {{ $dotColor }} mt-0.5"></span> @endif
                        </div>
                    @endfor
                </div>

                {{-- Legend --}}
                <div class="flex flex-wrap gap-3 mt-4 pt-3 border-t border-gray-100 dark:border-slate-700">
                    @foreach([['bg-green-500','Present'],['bg-orange-400','Late'],['bg-red-500','Absent'],['bg-yellow-400','Half Day'],['bg-blue-400','Holiday']] as [$color,$label])
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full {{ $color }}"></span>
                        <span class="text-xs text-gray-500 dark:text-slate-400">{{ $label }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>{{-- end tabs --}}

</div>
@endsection
