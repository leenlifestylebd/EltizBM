@extends('layouts.app')
@section('title', 'Production Team Overview')

@section('content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
    <div>
        <h2 class="text-base font-bold text-gray-900 dark:text-white">📊 Team Overview</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $label }}</p>
    </div>
    <a href="{{ route('production.tasks.create') }}"
       class="self-start sm:self-auto flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-primary-600 rounded-lg hover:bg-primary-700">
        + Assign Task
    </a>
</div>

{{-- Period Tabs --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">

    {{-- Period Type --}}
    <div class="flex gap-1 bg-gray-100 dark:bg-slate-700 p-1 rounded-lg mb-3">
        @foreach(['daily' => 'দৈনিক', 'weekly' => 'সাপ্তাহিক', 'monthly' => 'মাসিক'] as $p => $plabel)
        <a href="{{ route('production.admin-overview', ['period' => $p]) }}"
           class="flex-1 py-2 text-xs font-medium text-center rounded-lg transition-colors
                  {{ $period === $p ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700' }}">
            {{ $plabel }}
        </a>
        @endforeach
    </div>

    {{-- Period-specific picker --}}
    <form method="GET" action="{{ route('production.admin-overview') }}" class="flex flex-wrap gap-2 items-end">
        <input type="hidden" name="period" value="{{ $period }}">

        @if($period === 'daily')
        <div>
            <label class="block text-xs text-gray-400 mb-1">তারিখ</label>
            <input type="date" name="date" value="{{ request('date', today()->toDateString()) }}"
                   class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>

        @elseif($period === 'weekly')
        <div>
            <label class="block text-xs text-gray-400 mb-1">সপ্তাহ</label>
            <input type="week" name="week" value="{{ request('week', now()->format('Y-\WW')) }}"
                   class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>

        @else
        <div>
            <label class="block text-xs text-gray-400 mb-1">মাস</label>
            <select name="month"
                    class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                @foreach($months as $m)
                <option value="{{ $m['value'] }}" {{ ($month ?? now()->format('Y-m')) === $m['value'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-xs font-medium rounded-lg hover:bg-primary-700">দেখুন</button>
    </form>

    {{-- Quick Presets --}}
    <div class="flex flex-wrap gap-1.5 mt-2.5">
        @foreach($presets as $preset)
        @php
            $params = ['period' => $preset['period']];
            if (isset($preset['date']))  $params['date']  = $preset['date'];
            if (isset($preset['week']))  $params['week']  = $preset['week'];
            if (isset($preset['month'])) $params['month'] = $preset['month'];
            $isActive = $period === $preset['period'] && (
                (isset($preset['date'])  && request('date',  today()->toDateString())           === $preset['date'])  ||
                (isset($preset['week'])  && request('week',  now()->format('Y-\WW'))            === $preset['week'])  ||
                (isset($preset['month']) && ($month ?? now()->format('Y-m'))                    === $preset['month'])
            );
        @endphp
        <a href="{{ route('production.admin-overview', $params) }}"
           class="px-2.5 py-1 text-xs rounded-lg border transition-colors
                  {{ $isActive ? 'bg-primary-600 text-white border-primary-600' : 'bg-white dark:bg-slate-700 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-600 hover:bg-gray-50' }}">
            {{ $preset['label'] }}
        </a>
        @endforeach
    </div>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-900/10 rounded-xl p-4 border border-primary-100 dark:border-primary-900/40">
        <p class="text-xs text-primary-600 dark:text-primary-400 font-medium mb-1">মোট কাজ</p>
        <p class="text-2xl font-bold text-primary-700 dark:text-primary-300">{{ $summary['total_tasks'] }}</p>
        <p class="text-xs text-primary-500 mt-1">{{ $summary['total_employees'] }} জন worker</p>
    </div>
    <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-900/10 rounded-xl p-4 border border-orange-100 dark:border-orange-900/40">
        <p class="text-xs text-orange-600 dark:text-orange-400 font-medium mb-1">অপেক্ষায় / চলছে</p>
        <p class="text-2xl font-bold text-orange-700 dark:text-orange-300">{{ $summary['total_assigned'] + $summary['total_partial'] }}</p>
        <p class="text-xs text-orange-500 mt-1">{{ $summary['total_assigned'] }} নতুন · {{ $summary['total_partial'] }} আংশিক</p>
    </div>
    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-900/10 rounded-xl p-4 border border-green-100 dark:border-green-900/40">
        <p class="text-xs text-green-600 dark:text-green-400 font-medium mb-1">সম্পন্ন</p>
        <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $summary['total_completed'] }}</p>
        <p class="text-xs text-green-500 mt-1">{{ number_format($summary['total_pieces']) }} পিস তৈরি</p>
    </div>
    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-900/10 rounded-xl p-4 border border-purple-100 dark:border-purple-900/40">
        <p class="text-xs text-purple-600 dark:text-purple-400 font-medium mb-1">মোট আয়</p>
        <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">৳{{ number_format($summary['total_earning']) }}</p>
        <p class="text-xs text-purple-500 mt-1">অগ্রিম: ৳{{ number_format($summary['total_advance']) }}</p>
    </div>
</div>

{{-- Employee Cards --}}
<div class="space-y-3">
    @forelse($employeeStats as $stat)
    @php $emp = $stat['employee']; @endphp
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">

        {{-- Employee Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-50 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr($emp->name, 0, 2)) }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $emp->name }}</p>
                    <p class="text-xs text-gray-400">{{ $emp->designation ?? 'Worker' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($stat['total_tasks'] > 0)
                <div class="hidden sm:flex items-center gap-2">
                    <div class="w-20 bg-gray-100 dark:bg-slate-700 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full transition-all {{ $stat['completion_pct'] >= 100 ? 'bg-green-500' : ($stat['completion_pct'] >= 50 ? 'bg-primary-500' : 'bg-orange-400') }}"
                             style="width: {{ $stat['completion_pct'] }}%"></div>
                    </div>
                    <span class="text-xs font-bold {{ $stat['completion_pct'] >= 100 ? 'text-green-600' : 'text-gray-500' }}">{{ $stat['completion_pct'] }}%</span>
                </div>
                @endif
                @php
                    $dashParams = ['employee_id' => $emp->id, 'from' => $fromDate, 'to' => $toDate];
                @endphp
                <a href="{{ route('production.worker.dashboard', $dashParams) }}"
                   class="px-2.5 py-1 text-xs text-primary-600 border border-primary-200 dark:border-primary-800 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20">
                    Dashboard
                </a>
                <a href="{{ route('production.tasks.create') }}?employee_id={{ $emp->id }}"
                   class="px-2.5 py-1 text-xs text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                    + Task
                </a>
            </div>
        </div>

        {{-- Stats Grid --}}
        @if($stat['type'] === 'salary')
        {{-- Fixed salary employee: show salary / attendance / paid / net payable --}}
        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-gray-50 dark:divide-slate-700">
            <div class="px-3 py-3 text-center">
                <p class="text-xs text-gray-400 mb-1">মাসিক বেতন</p>
                <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400">৳{{ number_format($stat['monthly_salary'], 0) }}</p>
                <span class="text-xs text-indigo-400">Fixed</span>
            </div>
            <div class="px-3 py-3 text-center">
                <p class="text-xs text-gray-400 mb-1">উপস্থিতি</p>
                <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $stat['present_days'] }}</p>
                <span class="text-xs text-green-400">দিন</span>
            </div>
            <div class="px-3 py-3 text-center">
                <p class="text-xs text-gray-400 mb-1">পরিশোধিত</p>
                <p class="text-xl font-bold text-blue-600 dark:text-blue-400">৳{{ number_format($stat['total_paid'], 0) }}</p>
                <span class="text-xs text-blue-400">এই মাসে</span>
            </div>
            <div class="px-3 py-3 text-center">
                <p class="text-xs text-gray-400 mb-1">নেট পাওনা</p>
                <p class="text-xl font-bold {{ $stat['net_payable'] > 0 ? 'text-orange-600' : ($stat['net_payable'] < 0 ? 'text-green-600' : 'text-gray-400') }}">
                    ৳{{ number_format(abs($stat['net_payable']), 0) }}
                </p>
                <p class="text-xs {{ $stat['net_payable'] < 0 ? 'text-green-400' : 'text-orange-400' }}">
                    {{ $stat['net_payable'] < 0 ? 'অতিরিক্ত' : 'প্রদেয়' }}
                </p>
            </div>
        </div>
        @else
        {{-- Production employee: existing columns --}}
        <div class="grid grid-cols-3 md:grid-cols-6 divide-x divide-gray-50 dark:divide-slate-700">

            <div class="px-3 py-3 text-center">
                <p class="text-xs text-gray-400 mb-1">নতুন</p>
                <p class="text-xl font-bold {{ $stat['assigned'] > 0 ? 'text-primary-600' : 'text-gray-300 dark:text-slate-600' }}">{{ $stat['assigned'] }}</p>
                <span class="text-xs text-primary-500">Assigned</span>
            </div>

            <div class="px-3 py-3 text-center">
                <p class="text-xs text-gray-400 mb-1">আংশিক</p>
                <p class="text-xl font-bold {{ ($stat['partial'] + $stat['in_progress']) > 0 ? 'text-yellow-600' : 'text-gray-300 dark:text-slate-600' }}">
                    {{ $stat['partial'] + $stat['in_progress'] }}
                </p>
                <span class="text-xs text-yellow-500">Partial</span>
            </div>

            <div class="px-3 py-3 text-center">
                <p class="text-xs text-gray-400 mb-1">সম্পন্ন</p>
                <p class="text-xl font-bold {{ $stat['completed'] > 0 ? 'text-green-600' : 'text-gray-300 dark:text-slate-600' }}">{{ $stat['completed'] }}</p>
                <span class="text-xs text-green-500">Done</span>
            </div>

            <div class="px-3 py-3 text-center">
                <p class="text-xs text-gray-400 mb-1">পিস</p>
                <p class="text-xl font-bold text-gray-700 dark:text-slate-300">{{ number_format($stat['total_done']) }}</p>
                <p class="text-xs text-orange-500">বাকি {{ number_format($stat['total_pending']) }}</p>
            </div>

            <div class="px-3 py-3 text-center">
                <p class="text-xs text-gray-400 mb-1">আয়</p>
                <p class="text-xl font-bold text-green-600">৳{{ number_format($stat['total_earning']) }}</p>
                <p class="text-xs text-red-400">অগ্রিম ৳{{ number_format($stat['advance']) }}</p>
            </div>

            <div class="px-3 py-3 text-center">
                <p class="text-xs text-gray-400 mb-1">নেট পাওনা</p>
                <p class="text-xl font-bold {{ $stat['net_payable'] >= 0 ? 'text-purple-600' : 'text-orange-600' }}">
                    ৳{{ number_format(abs($stat['net_payable'])) }}
                </p>
                <p class="text-xs {{ $stat['net_payable'] < 0 ? 'text-orange-400' : 'text-purple-400' }}">
                    {{ $stat['net_payable'] < 0 ? 'অতিরিক্ত' : 'প্রদেয়' }}
                </p>
            </div>
        </div>
        @endif

        {{-- Footer bar --}}
        <div class="px-4 py-2 bg-gray-50 dark:bg-slate-700/30 flex items-center gap-2 flex-wrap">
            @if($stat['total_tasks'] > 0)
                <span class="text-xs text-gray-400">{{ $stat['total_tasks'] }}টি task</span>
                @if($stat['total_done'] > 0)
                <span class="px-2 py-0.5 text-xs bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-full text-green-700 dark:text-green-400">
                    ✓ {{ number_format($stat['total_done']) }} পিস তৈরি
                </span>
                @endif
                @if($stat['total_pending'] > 0)
                <span class="px-2 py-0.5 text-xs bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-full text-orange-700 dark:text-orange-400">
                    ⏳ {{ number_format($stat['total_pending']) }} পিস বাকি
                </span>
                @endif
            @else
                <span class="text-xs text-gray-400">এই সময়ে কোনো task নেই —</span>
                <a href="{{ route('production.tasks.create') }}?employee_id={{ $emp->id }}" class="text-xs text-primary-600 hover:underline">assign করুন</a>
            @endif
        </div>

    </div>
    @empty
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 py-16 text-center">
        <p class="text-sm text-gray-400">কোনো active employee নেই</p>
    </div>
    @endforelse
</div>

@endsection
