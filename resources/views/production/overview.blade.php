@extends('layouts.app')
@section('title', 'Production Overview')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Production Overview</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ today()->format('d F Y') }}</p>
    </div>
    {{-- Date filter --}}
    <form method="GET" class="flex items-center gap-2">
        <input type="date" name="date" value="{{ request('date', today()->format('Y-m-d')) }}"
               class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
        <button type="submit" class="px-3 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 transition-colors">View</button>
    </form>
</div>

{{-- Summary Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700 text-center">
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['total_qty'] }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Total Produced</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-primary-100 dark:border-primary-900/30 text-center">
        <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $summary['workers_logged'] }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Workers Logged</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-green-100 dark:border-green-900/30 text-center">
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $summary['target_achieved'] }}%</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Target Hit</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-purple-100 dark:border-purple-900/30 text-center">
        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $summary['avg_quality'] }}%</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Avg Quality</p>
    </div>
</div>

{{-- Employee Production Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden mb-4">
    <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Employee Performance — {{ \Carbon\Carbon::parse(request('date', today()))->format('d M Y') }}</h3>
    </div>

    {{-- Desktop --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Employee</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Attendance</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Tasks</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Produced</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Target</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Achievement</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Quality</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @foreach($employeeStats as $stat)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3">
                        <a href="{{ route('employees.show', $stat['employee']) }}" class="flex items-center gap-2 hover:text-primary-600 transition-colors">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold
                                {{ ['bg-primary-500','bg-purple-500','bg-green-500','bg-orange-500','bg-pink-500'][crc32($stat['employee']->name) % 5] }}">
                                {{ strtoupper(substr($stat['employee']->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $stat['employee']->name }}</p>
                                <p class="text-xs text-gray-400 dark:text-slate-500">{{ $stat['employee']->designation ?? '—' }}</p>
                            </div>
                        </a>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php $attStatus = $stat['attendance']?->status; @endphp
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $attStatus === 'present' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                               ($attStatus === 'absent'  ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                               ($attStatus === 'late'    ? 'bg-yellow-100 text-yellow-700' :
                                                           'bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-slate-400')) }}">
                            {{ $attStatus ? ucfirst(str_replace('_',' ',$attStatus)) : 'Not marked' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($stat['logs']->count() > 0)
                            @foreach($stat['logs']->take(2) as $log)
                                <p class="text-xs text-gray-600 dark:text-slate-300 truncate max-w-32">{{ $log->task_name }}</p>
                            @endforeach
                            @if($stat['logs']->count() > 2)
                                <p class="text-xs text-gray-400">+{{ $stat['logs']->count() - 2 }} more</p>
                            @endif
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-base font-bold text-gray-900 dark:text-white">{{ $stat['total_qty'] }}</td>
                    <td class="px-4 py-3 text-center text-sm text-gray-500 dark:text-slate-400">{{ $stat['target_qty'] > 0 ? $stat['target_qty'] : '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($stat['target_qty'] > 0)
                            @php $pct = min(100, round(($stat['total_qty'] / $stat['target_qty']) * 100)); @endphp
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-100 dark:bg-slate-700 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $pct >= 100 ? 'bg-green-500' : ($pct >= 70 ? 'bg-yellow-500' : 'bg-red-400') }}"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-xs font-medium {{ $pct >= 100 ? 'text-green-600 dark:text-green-400' : ($pct >= 70 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">{{ $pct }}%</span>
                            </div>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($stat['avg_quality'] > 0)
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $stat['avg_quality'] >= 90 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : ($stat['avg_quality'] >= 70 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                            {{ $stat['avg_quality'] }}%
                        </span>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden divide-y divide-gray-50 dark:divide-slate-700">
        @foreach($employeeStats as $stat)
        <div class="p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold
                    {{ ['bg-primary-500','bg-purple-500','bg-green-500','bg-orange-500','bg-pink-500'][crc32($stat['employee']->name) % 5] }}">
                    {{ strtoupper(substr($stat['employee']->name, 0, 2)) }}
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-sm text-gray-900 dark:text-white">{{ $stat['employee']->name }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500">{{ $stat['employee']->designation ?? '—' }}</p>
                </div>
                @php $attStatus = $stat['attendance']?->status; @endphp
                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                    {{ $attStatus === 'present' ? 'bg-green-100 text-green-700' : ($attStatus === 'absent' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500') }}">
                    {{ $attStatus ? ucfirst($attStatus) : '—' }}
                </span>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2 text-center">
                    <p class="text-base font-bold text-gray-900 dark:text-white">{{ $stat['total_qty'] }}</p>
                    <p class="text-xs text-gray-400">Produced</p>
                </div>
                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2 text-center">
                    <p class="text-base font-bold text-gray-900 dark:text-white">{{ $stat['target_qty'] ?: '—' }}</p>
                    <p class="text-xs text-gray-400">Target</p>
                </div>
                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2 text-center">
                    <p class="text-base font-bold {{ $stat['avg_quality'] >= 90 ? 'text-green-600' : 'text-yellow-600' }}">{{ $stat['avg_quality'] > 0 ? $stat['avg_quality'].'%' : '—' }}</p>
                    <p class="text-xs text-gray-400">Quality</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

@endsection
