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
                {{ ['bg-blue-500','bg-purple-500','bg-green-500','bg-orange-500','bg-pink-500','bg-teal-500'][crc32($employee->name) % 6] }}">
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
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 text-center">
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $employee->todayProduction() }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400">Today</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-blue-100 dark:border-blue-900/30 p-3 text-center">
            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $monthProduction }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400">This Month</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-green-100 dark:border-green-900/30 p-3 text-center">
            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $monthAttendance }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400">Days Present</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-purple-100 dark:border-purple-900/30 p-3 text-center">
            <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ $avgQuality ?? 0 }}%</p>
            <p class="text-xs text-gray-500 dark:text-slate-400">Avg Quality</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 mb-4 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl">
        @foreach([
            ['key'=>'overview',    'label'=>'Overview'],
            ['key'=>'production',  'label'=>'Production'],
            ['key'=>'attendance',  'label'=>'Attendance'],
        ] as $t)
        <button @click="tab = '{{ $t['key'] }}'"
                class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors"
                :class="tab === '{{ $t['key'] }}' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300'">
            {{ $t['label'] }}
        </button>
        @endforeach
    </div>

    {{-- Tab: Overview --}}
    <div x-show="tab === 'overview'">
        {{-- Production Chart (last 7 days) --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Last 7 Days Production</h3>
            <div class="flex items-end gap-1 h-24">
                @foreach($last7Days as $day)
                <div class="flex-1 flex flex-col items-center gap-1">
                    <span class="text-xs text-gray-400 dark:text-slate-500">{{ $day['qty'] }}</span>
                    <div class="w-full rounded-t-sm bg-blue-500 dark:bg-blue-600 transition-all"
                         style="height: {{ $day['qty'] > 0 ? max(4, ($day['qty'] / max(collect($last7Days)->max('qty'), 1)) * 80) : 4 }}px; min-height:4px;"></div>
                    <span class="text-xs text-gray-400 dark:text-slate-500">{{ $day['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Recent Production Logs --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Production</h3>
            </div>
            @forelse($recentLogs as $log)
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 dark:border-slate-700 last:border-0">
                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $log->task_name }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                        {{ $log->log_date->format('d M Y') }}
                        @if($log->product) · {{ $log->product->name }} @endif
                    </p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $log->quantity }}</p>
                    @if($log->target_quantity > 0)
                        <p class="text-xs {{ $log->quantity >= $log->target_quantity ? 'text-green-500' : 'text-yellow-500' }}">
                            / {{ $log->target_quantity }}
                        </p>
                    @endif
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-sm text-gray-400 dark:text-slate-500">No production logs yet</div>
            @endforelse
        </div>
    </div>

    {{-- Tab: Production --}}
    <div x-show="tab === 'production'" x-cloak>
        {{-- Log Form --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Log Production</h3>
            <form method="POST" action="{{ route('employees.production.store', $employee) }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Task / Product <span class="text-red-500">*</span></label>
                        <input type="text" name="task_name" required
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g. Packing boxes, Cutting fabric...">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Product (optional)</label>
                        <select name="product_id" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select product</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Quantity Done <span class="text-red-500">*</span></label>
                        <input type="number" name="quantity" required min="0"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Target Quantity</label>
                        <input type="number" name="target_quantity" min="0"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Date <span class="text-red-500">*</span></label>
                        <input type="date" name="log_date" required value="{{ today()->format('Y-m-d') }}"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Quality Rate (%)</label>
                        <input type="number" name="quality_rate" min="0" max="100" value="100"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Start Time</label>
                            <input type="time" name="start_time"
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">End Time</label>
                            <input type="time" name="end_time"
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Notes</label>
                        <input type="text" name="notes"
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Optional notes...">
                    </div>
                </div>
                <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                    Log Production
                </button>
            </form>
        </div>

        {{-- Production Logs Table --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Production History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-slate-700/50">
                            <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Date</th>
                            <th class="text-left px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Task</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Done / Target</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Quality</th>
                            <th class="text-center px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-slate-400">Hours</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                        @forelse($productionLogs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-4 py-2.5 text-xs text-gray-600 dark:text-slate-300 whitespace-nowrap">{{ $log->log_date->format('d M Y') }}</td>
                            <td class="px-4 py-2.5">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $log->task_name }}</p>
                                @if($log->product)<p class="text-xs text-gray-400 dark:text-slate-500">{{ $log->product->name }}</p>@endif
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="text-sm font-bold {{ $log->target_quantity > 0 && $log->quantity >= $log->target_quantity ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $log->quantity }}
                                </span>
                                @if($log->target_quantity > 0)
                                    <span class="text-xs text-gray-400 dark:text-slate-500"> / {{ $log->target_quantity }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $log->quality_rate >= 90 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : ($log->quality_rate >= 70 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                    {{ $log->quality_rate }}%
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center text-xs text-gray-500 dark:text-slate-400">
                                @if($log->start_time && $log->end_time)
                                    {{ \Carbon\Carbon::parse($log->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($log->end_time)->format('H:i') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-slate-500">No production logs yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($productionLogs->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $productionLogs->links() }}</div>
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
                               class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Status <span class="text-red-500">*</span></label>
                        <select name="status" required class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                   class="w-full px-2 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Check Out</label>
                            <input type="time" name="check_out"
                                   class="w-full px-2 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                        $colors = ['present'=>'bg-green-500','absent'=>'bg-red-400','late'=>'bg-yellow-400','half_day'=>'bg-orange-400','holiday'=>'bg-blue-400'];
                    @endphp
                    <div class="aspect-square rounded-lg flex items-center justify-center text-xs font-medium relative
                        {{ $isToday ? 'ring-2 ring-blue-500' : '' }}
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
                        {{ ['present'=>'bg-green-100 dark:bg-green-900/30','absent'=>'bg-red-100 dark:bg-red-900/30','late'=>'bg-yellow-100 dark:bg-yellow-900/30','half_day'=>'bg-orange-100 dark:bg-orange-900/30','holiday'=>'bg-blue-100 dark:bg-blue-900/30'][$record->status] ?? 'bg-gray-100 dark:bg-slate-700' }}">
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
                        {{ ['present'=>'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400','absent'=>'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400','late'=>'bg-yellow-100 text-yellow-700','half_day'=>'bg-orange-100 text-orange-700','holiday'=>'bg-blue-100 text-blue-700'][$record->status] ?? 'bg-gray-100 text-gray-600' }}">
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
