{{-- ============================================================
     FILE: resources/views/attendance/overview.blade.php
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Attendance Overview')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Attendance</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Daily attendance tracker</p>
    </div>
    <form method="GET" class="flex items-center gap-2">
        <input type="date" name="date" value="{{ $date->format('Y-m-d') }}"
               class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors">View</button>
    </form>
</div>

{{-- Summary --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
    @foreach([
        ['label'=>'Present','val'=>$summary['present'],   'color'=>'green'],
        ['label'=>'Absent', 'val'=>$summary['absent'],    'color'=>'red'],
        ['label'=>'Late',   'val'=>$summary['late'],      'color'=>'yellow'],
        ['label'=>'Not Marked','val'=>$summary['not_marked'],'color'=>'gray'],
    ] as $s)
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-{{ $s['color'] }}-100 dark:border-{{ $s['color'] }}-900/30 text-center">
        <p class="text-xl font-bold text-{{ $s['color'] }}-600 dark:text-{{ $s['color'] }}-400">{{ $s['val'] }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $s['label'] }}</p>
    </div>
    @endforeach
</div>

{{-- Bulk Mark Attendance --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-4" x-data="{ bulkStatus: 'present' }">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Bulk Mark — {{ $date->format('d M Y') }}</h3>
        <div class="flex items-center gap-2">
            <select x-model="bulkStatus" class="text-xs px-2 py-1.5 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="holiday">Holiday</option>
            </select>
            <form method="POST" action="{{ route('attendance.index') }}">
                @csrf
                <input type="hidden" name="bulk_status" x-bind:value="bulkStatus">
                <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
                <button type="submit" class="px-3 py-1.5 text-xs font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Mark All
                </button>
            </form>
        </div>
    </div>

    {{-- Employee List --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
        @foreach($employees as $emp)
        @php $att = $records->get($emp->id); @endphp
        <form method="POST" action="{{ route('employees.attendance.store', $emp) }}" class="flex items-center gap-2 p-2.5 bg-gray-50 dark:bg-slate-700/50 rounded-xl">
            @csrf
            <input type="hidden" name="attendance_date" value="{{ $date->format('Y-m-d') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0
                {{ ['bg-blue-500','bg-purple-500','bg-green-500','bg-orange-500','bg-pink-500'][crc32($emp->name) % 5] }}">
                {{ strtoupper(substr($emp->name, 0, 2)) }}
            </div>
            <span class="flex-1 text-xs font-medium text-gray-900 dark:text-white truncate">{{ $emp->name }}</span>
            <select name="status" class="text-xs px-2 py-1 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-600 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @foreach(['present'=>'✅ Present','absent'=>'❌ Absent','late'=>'⏰ Late','half_day'=>'½ Half','holiday'=>'🏖️ Holiday'] as $val => $lbl)
                    <option value="{{ $val }}" {{ ($att?->status ?? '') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
            <button type="submit" class="p-1.5 text-green-600 bg-green-100 dark:bg-green-900/30 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors flex-shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </button>
        </form>
        @endforeach
    </div>
</div>

@endsection
