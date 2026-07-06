@extends('layouts.app')
@section('title', 'My Dashboard')

@php $isWorker = auth()->user()->role === 'employee'; @endphp

@section('content')
<div x-data="workerDash()" class="max-w-2xl mx-auto pb-8">

    {{-- Success Alert --}}
    @if(session('success'))
    <div class="mb-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl flex items-center gap-2">
        <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <p class="text-xs text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow">
                {{ strtoupper(substr($employee->name, 0, 2)) }}
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">{{ $employee->name }}</h2>
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ $employee->designation ?? 'Production Worker' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if(in_array(auth()->user()->role, ['admin', 'owner']) && $allEmployees->count() > 1)
            <select onchange="window.location.href='{{ route('production.worker.dashboard') }}?employee_id='+this.value+'&from={{ $from }}&to={{ $to }}'"
                    class="px-2 py-1.5 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none">
                @foreach($allEmployees as $emp)
                <option value="{{ $emp->id }}" {{ $emp->id === $employee->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
            @endif
            @include('production.partials.notification-bell')
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
        <form method="GET" class="space-y-2">
            @if(in_array(auth()->user()->role, ['admin', 'owner']))
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            @endif
            <div class="flex gap-2">
                <input type="date" name="from" value="{{ $from }}"
                       class="flex-1 px-2 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                <input type="date" name="to" value="{{ $to }}"
                       class="flex-1 px-2 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                <button type="submit" class="px-3 py-2 bg-primary-600 text-white text-xs rounded-lg font-medium">Filter</button>
            </div>
            <div class="flex gap-1.5 flex-wrap">
                @foreach([
                    ['label'=>'Today',      'from'=>today()->toDateString(),                              'to'=>today()->toDateString()],
                    ['label'=>'This Week',  'from'=>today()->startOfWeek()->toDateString(),               'to'=>today()->toDateString()],
                    ['label'=>'This Month', 'from'=>today()->startOfMonth()->toDateString(),              'to'=>today()->toDateString()],
                    ['label'=>'Last Month', 'from'=>today()->subMonth()->startOfMonth()->toDateString(),  'to'=>today()->subMonth()->endOfMonth()->toDateString()],
                ] as $q)
                <a href="{{ route('production.worker.dashboard', array_merge(['from'=>$q['from'], 'to'=>$q['to']], in_array(auth()->user()->role, ['admin','owner']) ? ['employee_id'=>$employee->id] : [])) }}"
                   class="px-2.5 py-1 text-xs rounded-lg border transition-colors
                          {{ $from === $q['from'] && $to === $q['to'] ? 'bg-primary-600 text-white border-primary-600' : 'bg-white dark:bg-slate-700 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-600 hover:bg-gray-50' }}">
                    {{ $q['label'] }}
                </a>
                @endforeach
            </div>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-3.5 border border-green-100 dark:border-green-900/40">
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">মোট আয়</p>
            <p class="text-xl font-bold text-green-600 dark:text-green-400">৳{{ number_format($totalEarning) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ number_format($totalQty) }} পিস</p>
        </div>
        <div class="bg-gradient-to-br from-primary-50 to-primary-50 dark:from-primary-900/20 dark:to-primary-900/20 rounded-xl p-3.5 border border-primary-100 dark:border-primary-900/40">
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">অগ্রিম নেওয়া</p>
            <p class="text-xl font-bold text-red-500 dark:text-red-400">৳{{ number_format($totalAdvance) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">কাটা যাবে</p>
        </div>
        <div class="col-span-2 bg-gradient-to-br from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 rounded-xl p-3.5 border border-purple-100 dark:border-purple-900/40">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">{{ $netPayable >= 0 ? 'এখন পাওনা (নেট)' : 'অতিরিক্ত নেওয়া হয়েছে' }}</p>
                    <p class="text-2xl font-bold {{ $netPayable >= 0 ? 'text-purple-600 dark:text-purple-400' : 'text-orange-600' }}">৳{{ number_format(abs($netPayable)) }}</p>
                    @if($netPayable < 0)
                        <p class="text-xs text-orange-500 mt-0.5">পরের মাসে কাটা যাবে</p>
                    @endif
                </div>
                <div class="text-right">
                    @if(abs($openingBalance ?? 0) >= 1)
                        <p class="text-xs text-gray-400">আগের জের</p>
                        <p class="text-xs {{ $openingBalance >= 0 ? 'text-green-600' : 'text-orange-500' }}">
                            {{ $openingBalance < 0 ? '−' : '' }}৳{{ number_format(abs($openingBalance)) }}
                        </p>
                    @endif
                    <p class="text-xs text-gray-400 mt-1">এ মাসের আয়</p>
                    <p class="text-xs text-green-600">৳{{ number_format($totalEarning) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Task Overview Card --}}
    @if($taskStats['total'] > 0)
    @php $taskPct = round(($taskStats['completed'] / $taskStats['total']) * 100); @endphp
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 border border-primary-100 dark:border-primary-900/30 mb-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-semibold text-gray-700 dark:text-slate-300">কাজের অগ্রগতি</p>
            <span class="text-sm font-bold text-primary-600 dark:text-primary-400">{{ $taskPct }}%</span>
        </div>
        <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-2 mb-2.5">
            <div class="bg-primary-500 h-2 rounded-full transition-all" style="width: {{ $taskPct }}%"></div>
        </div>
        <div class="flex gap-3 text-xs">
            <span class="text-gray-500">মোট: <strong class="text-gray-700 dark:text-white">{{ $taskStats['total'] }}</strong></span>
            <span class="text-gray-500">চলছে: <strong class="text-yellow-600">{{ ($taskStats['partial'] ?? 0) + ($taskStats['in_progress'] ?? 0) }}</strong></span>
            <span class="text-gray-500">শেষ: <strong class="text-green-600">{{ $taskStats['completed'] }}</strong></span>
            <span class="text-gray-500">বাকি: <strong class="text-orange-600">{{ $taskStats['assigned'] }}</strong></span>
        </div>
    </div>
    @endif

    {{-- Tabs — Tasks first for workers --}}
    <div x-data="{ tab: '{{ $isWorker ? 'tasks' : 'production' }}' }">
        <div class="flex gap-1 mb-4 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl overflow-x-auto">
            @if($isWorker)
            <button @click="tab = 'tasks'" class="flex-1 min-w-max py-2 px-3 text-xs font-medium rounded-lg transition-colors"
                    :class="tab === 'tasks' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
                ✅ কাজ {{ $taskStats['total'] > 0 ? '('.$taskStats['total'].')' : '' }}
            </button>
            @endif
            <button @click="tab = 'production'" class="flex-1 min-w-max py-2 px-3 text-xs font-medium rounded-lg transition-colors"
                    :class="tab === 'production' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
                📦 উৎপাদন
            </button>
            <button @click="tab = 'payments'" class="flex-1 min-w-max py-2 px-3 text-xs font-medium rounded-lg transition-colors"
                    :class="tab === 'payments' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
                💰 পেমেন্ট
            </button>
            <button @click="tab = 'report'" class="flex-1 min-w-max py-2 px-3 text-xs font-medium rounded-lg transition-colors"
                    :class="tab === 'report' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
                📊 রিপোর্ট
            </button>
            @if(!$isWorker)
            <button @click="tab = 'tasks'" class="flex-1 min-w-max py-2 px-3 text-xs font-medium rounded-lg transition-colors"
                    :class="tab === 'tasks' ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400'">
                ✅ Tasks
            </button>
            @endif
        </div>

        {{-- Tasks Tab --}}
        <div x-show="tab === 'tasks'">
            @forelse($myTasks as $task)
            @php
                $pct = $task->completion_percent;
                $isPending = $task->pending_quantity > 0;
                $statusColor = match($task->status) {
                    'completed'   => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
                    'partial'     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
                    'in_progress' => 'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-400',
                    default       => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300',
                };
                $barColor = $task->status === 'completed' ? 'bg-green-500' : ($pct > 50 ? 'bg-primary-500' : 'bg-orange-400');
            @endphp
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 mb-3 overflow-hidden">
                {{-- Task Header --}}
                <div class="p-4">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0 pr-2">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $task->product_name }}</p>
                            <div class="flex flex-wrap gap-1.5 mt-1">
                                @if($task->color)
                                <span class="px-2 py-0.5 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 rounded text-xs">{{ $task->color }}</span>
                                @endif
                                @if($task->size)
                                <span class="px-2 py-0.5 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 rounded text-xs">সাইজ: {{ $task->size }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold flex-shrink-0 {{ $statusColor }}">
                            {{ match($task->status) {
                                'completed'   => 'সম্পন্ন',
                                'partial'     => 'আংশিক',
                                'in_progress' => 'চলছে',
                                'assigned'    => 'নতুন',
                                default       => ucfirst($task->status)
                            } }}
                        </span>
                    </div>

                    {{-- Progress --}}
                    <div class="mb-3">
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="text-gray-500">সম্পন্ন: <strong class="text-gray-700 dark:text-white">{{ $task->completed_quantity }}</strong> / {{ $task->assigned_quantity }} পিস</span>
                            <span class="font-semibold {{ $pct >= 100 ? 'text-green-600' : 'text-primary-600' }}">{{ $pct }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-2.5">
                            <div class="{{ $barColor }} h-2.5 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>

                    {{-- Stats Row --}}
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <div class="flex gap-3">
                            <span>বাকি: <strong class="{{ $isPending ? 'text-orange-600' : 'text-green-600' }}">{{ $task->pending_quantity }} পিস</strong></span>
                            @if($task->deadline)
                            <span>ডেডলাইন: <strong class="{{ $task->isOverdue() && $isPending ? 'text-red-600' : 'text-gray-600 dark:text-slate-300' }}">{{ $task->deadline->format('d M') }}</strong></span>
                            @endif
                        </div>
                        <span class="text-gray-400">{{ $task->assigned_at->diffForHumans() }}</span>
                    </div>

                    @if($task->notes)
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-2 italic border-t border-gray-50 dark:border-slate-700 pt-2">{{ $task->notes }}</p>
                    @endif
                </div>

                {{-- Submit Button --}}
                @if($isPending)
                @php $taskRate = $task->productionRate?->rate ?? 0; @endphp
                <div class="px-4 pb-4">
                    <button
                        @click="$dispatch('open-submit-modal', {
                            taskId: {{ $task->id }},
                            taskName: '{{ addslashes($task->product_name) }}',
                            color: '{{ addslashes($task->color ?? '') }}',
                            size: '{{ addslashes($task->size ?? '') }}',
                            max: {{ $task->pending_quantity }},
                            rate: {{ $taskRate }}
                        })"
                        class="w-full py-3 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        কাজ জমা দিন (বাকি {{ $task->pending_quantity }} পিস)
                        @if($taskRate > 0)<span class="opacity-70 text-xs font-normal"> · ৳{{ number_format($taskRate) }}/পিস</span>@endif
                    </button>
                </div>
                @else
                <div class="px-4 pb-4">
                    <div class="w-full py-2.5 bg-green-50 dark:bg-green-900/20 text-green-600 text-sm font-medium rounded-xl text-center flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        সম্পূর্ণ হয়েছে
                    </div>
                </div>
                @endif
            </div>
            @empty
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 py-16 text-center">
                <svg class="w-12 h-12 text-gray-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p class="text-sm text-gray-400 dark:text-slate-500">এখন কোনো কাজ assign করা নেই</p>
            </div>
            @endforelse
        </div>

        {{-- Production Tab --}}
        <div x-show="tab === 'production'" x-cloak>
            <div class="space-y-2">
                @forelse($entries as $entry)
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3.5">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ optional($entry->productionRate)->product_name ?? $entry->notes ?? 'Entry' }}
                            </p>
                            @if(optional($entry->productionRate)->size || optional($entry->productionRate)->color)
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ implode(' · ', array_filter([optional($entry->productionRate)->color, optional($entry->productionRate)->size])) }}
                            </p>
                            @endif
                            <p class="text-xs text-gray-400 mt-0.5">{{ $entry->entry_date->format('d M Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-green-600">৳{{ number_format($entry->amount) }}</p>
                            <p class="text-xs text-gray-400">{{ $entry->quantity }} পিস</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 py-12 text-center">
                    <p class="text-sm text-gray-400">এই সময়ে কোনো এন্ট্রি নেই</p>
                </div>
                @endforelse
                @if($entries->count() > 0)
                <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-3 flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-700 dark:text-slate-300">মোট: {{ number_format($totalQty) }} পিস</span>
                    <span class="text-sm font-bold text-green-600">৳{{ number_format($totalEarning) }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Payments Tab --}}
        <div x-show="tab === 'payments'" x-cloak>
            <div class="space-y-2">
                @forelse($payments as $payment)
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3.5 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0
                            {{ in_array($payment->type, ['advance','deduction']) ? 'bg-red-100 dark:bg-red-900/30' : 'bg-green-100 dark:bg-green-900/30' }}">
                            @if(in_array($payment->type, ['advance','deduction']))
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                            @else
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ match($payment->type) { 'advance' => 'অগ্রিম', 'salary' => 'বেতন', 'bonus' => 'বোনাস', 'deduction' => 'কর্তন', default => ucfirst($payment->type) } }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $payment->payment_date->format('d M Y') }} · {{ ucfirst($payment->payment_method ?? '') }}</p>
                            @if($payment->notes)
                            <p class="text-xs text-gray-400">{{ $payment->notes }}</p>
                            @endif
                        </div>
                    </div>
                    <p class="font-bold text-sm flex-shrink-0 {{ in_array($payment->type, ['advance','deduction']) ? 'text-red-600' : 'text-green-600' }}">
                        {{ in_array($payment->type, ['advance','deduction']) ? '-' : '+' }}৳{{ number_format($payment->amount) }}
                    </p>
                </div>
                @empty
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 py-12 text-center">
                    <p class="text-sm text-gray-400">এই সময়ে কোনো পেমেন্ট নেই</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Report Tab --}}
        <div x-show="tab === 'report'" x-cloak>
            <div class="space-y-4">
                {{-- Chart --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">দৈনিক পারফরম্যান্স</h3>
                    <div class="relative h-52">
                        <canvas id="earningChart"></canvas>
                    </div>
                </div>

                {{-- Daily Breakdown --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">দৈনিক বিবরণ</h3>
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-slate-700">
                        @forelse($dailyReport as $day)
                        <div class="px-4 py-3 flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-slate-300">{{ \Carbon\Carbon::parse($day->date)->format('d M Y, D') }}</span>
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-gray-400">{{ number_format($day->total_qty) }} পিস</span>
                                <span class="text-sm font-semibold text-green-600">৳{{ number_format($day->total_amount) }}</span>
                            </div>
                        </div>
                        @empty
                        <div class="px-4 py-8 text-center text-sm text-gray-400">কোনো ডেটা নেই</div>
                        @endforelse
                    </div>
                </div>

                {{-- Product Breakdown --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">পণ্য অনুযায়ী</h3>
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-slate-700">
                        @forelse($productReport as $prod)
                        <div class="px-4 py-3 flex items-center justify-between">
                            <span class="text-sm text-gray-900 dark:text-white">{{ $prod->product_name }}</span>
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-gray-400">{{ number_format($prod->total_qty) }} পিস</span>
                                <span class="text-sm font-semibold text-green-600">৳{{ number_format($prod->total_amount) }}</span>
                            </div>
                        </div>
                        @empty
                        <div class="px-4 py-8 text-center text-sm text-gray-400">কোনো ডেটা নেই</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Submit Work Modal (Bottom Sheet on Mobile) --}}
<div x-data="{
        show: false, taskId: null, taskName: '', taskColor: '', taskSize: '',
        maxQty: 1, qty: 1, defaultRate: 0, rateUsed: 0,
        get earning() { return Math.round(this.qty * this.rateUsed).toLocaleString(); }
     }"
     @open-submit-modal.window="
        show = true;
        taskId    = $event.detail.taskId;
        taskName  = $event.detail.taskName;
        taskColor = $event.detail.color;
        taskSize  = $event.detail.size;
        maxQty    = $event.detail.max;
        qty       = Math.min(1, $event.detail.max);
        defaultRate = $event.detail.rate || 0;
        rateUsed    = $event.detail.rate || 0;
     "
     x-show="show"
     x-cloak
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center"
     style="display: none;">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50" @click="show = false"></div>

    {{-- Sheet --}}
    <div class="relative bg-white dark:bg-slate-800 rounded-t-2xl sm:rounded-2xl w-full max-w-sm sm:mx-4 p-5 z-10"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="transform translate-y-full sm:translate-y-0 sm:opacity-0 sm:scale-95"
         x-transition:enter-end="transform translate-y-0 sm:opacity-100 sm:scale-100">

        {{-- Handle (mobile) --}}
        <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-4 sm:hidden"></div>

        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-0.5">কাজ জমা দিন</h3>
        <p class="text-sm font-medium text-gray-700 dark:text-slate-300" x-text="taskName"></p>
        <p class="text-xs text-primary-500 mb-4" x-show="taskColor || taskSize">
            <span x-text="[taskColor, taskSize].filter(Boolean).join(' · ')"></span>
        </p>

        <form method="POST" :action="'{{ url('production/tasks') }}/' + taskId + '/submit'">
            @csrf

            {{-- Quantity --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                    কতটি পিস সম্পন্ন হয়েছে?
                    <span class="text-gray-400 font-normal">(সর্বোচ্চ <span x-text="maxQty"></span>)</span>
                </label>
                <div class="flex items-center gap-3">
                    <button type="button" @click="qty = Math.max(1, qty - 1)"
                            class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-white text-xl font-bold flex items-center justify-center active:bg-gray-200">−</button>
                    <input type="number" name="submitted_quantity"
                           x-model.number="qty"
                           :max="maxQty" min="1" required
                           class="flex-1 h-12 text-center text-xl font-bold border-2 border-primary-300 dark:border-primary-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:border-primary-500">
                    <button type="button" @click="qty = Math.min(maxQty, qty + 1)"
                            class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-white text-xl font-bold flex items-center justify-center active:bg-gray-200">+</button>
                </div>
                <p class="text-xs text-center text-gray-400 mt-1.5">বাকি থাকবে: <strong x-text="maxQty - qty"></strong> পিস</p>
            </div>

            {{-- Rate Override --}}
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                    প্রতি পিস রেট (৳)
                    <span x-show="defaultRate > 0" class="text-xs text-gray-400 font-normal">
                        — assigned: ৳<span x-text="defaultRate"></span>
                    </span>
                </label>
                <input type="number" name="rate_used" x-model.number="rateUsed"
                       min="0" step="0.01"
                       class="w-full px-4 py-3 text-sm border border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                <p x-show="defaultRate > 0 && rateUsed != defaultRate && rateUsed > 0"
                   class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                    ⚠️ Assigned rate ৳<span x-text="defaultRate"></span> থেকে আলাদা
                </p>
            </div>

            {{-- Submission Date (back-date supported) --}}
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">তারিখ</label>
                <input type="date" name="submitted_date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}"
                       class="w-full px-4 py-3 text-sm border border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                <p class="text-xs text-gray-400 mt-1">আগের দিনের কাজ হলে সেই তারিখ দিন।</p>
            </div>

            {{-- Earning Preview --}}
            <div x-show="rateUsed > 0"
                 class="mb-4 flex items-center justify-between bg-green-50 dark:bg-green-900/20 rounded-xl px-4 py-2.5">
                <span class="text-sm text-gray-500 dark:text-slate-400">
                    <span x-text="qty"></span> পিস × ৳<span x-text="rateUsed"></span>
                </span>
                <span class="text-base font-bold text-green-600 dark:text-green-400">
                    = ৳<span x-text="earning"></span>
                </span>
            </div>

            <div class="mb-4">
                <input type="text" name="quality_note" placeholder="মন্তব্য (ঐচ্ছিক)"
                       class="w-full px-4 py-3 text-sm border border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <div class="flex gap-3">
                <button type="button" @click="show = false"
                        class="flex-1 py-3.5 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 text-sm font-medium rounded-xl">
                    বাতিল
                </button>
                <button type="submit"
                        class="flex-1 py-3.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-bold rounded-xl flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    জমা দিন
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function workerDash() {
    return {}
}

const dailyData = @json($dailyReport);
const labels    = dailyData.map(d => {
    const date = new Date(d.date);
    return date.toLocaleDateString('en-BD', { day: 'numeric', month: 'short' });
});
const amounts    = dailyData.map(d => parseFloat(d.total_amount));
const quantities = dailyData.map(d => parseInt(d.total_qty));

const ctx = document.getElementById('earningChart');
if (ctx) {
    new Chart(ctx, {
        data: {
            labels: labels.length ? labels : ['কোনো ডেটা নেই'],
            datasets: [
                {
                    type: 'bar',
                    label: 'আয় (৳)',
                    data: amounts.length ? amounts : [0],
                    backgroundColor: 'rgba(59,130,246,0.5)',
                    borderColor: 'rgba(59,130,246,1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    yAxisID: 'y',
                },
                {
                    type: 'line',
                    label: 'পিস',
                    data: quantities.length ? quantities : [0],
                    borderColor: 'rgba(16,185,129,1)',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(16,185,129,1)',
                    tension: 0.3,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: true, position: 'top', labels: { font: { size: 10 }, boxWidth: 10, padding: 8 } },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.dataset.label.includes('আয়')
                            ? '৳' + ctx.raw.toLocaleString()
                            : ctx.raw + ' পিস'
                    }
                }
            },
            scales: {
                y:  { beginAtZero: true, position: 'left',  ticks: { callback: v => '৳'+v.toLocaleString(), font:{size:10} }, grid: { color:'rgba(0,0,0,0.05)' } },
                y1: { beginAtZero: true, position: 'right', ticks: { callback: v => v+' পিস', font:{size:10} }, grid: { drawOnChartArea: false } },
                x:  { ticks: { font: { size: 10 } }, grid: { display: false } }
            }
        }
    });
}
</script>
@endpush
