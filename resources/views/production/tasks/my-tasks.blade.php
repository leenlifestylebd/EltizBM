@extends('layouts.app')
@section('title', 'My Tasks')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">My Tasks</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $employee->name }} — assigned work</p>
    </div>
    <a href="{{ route('production.worker.dashboard') }}"
       class="px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50">
        ← Dashboard
    </a>
</div>

@if(session('success'))
<div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl text-xs text-green-700 dark:text-green-400">
    {{ session('success') }}
</div>
@endif

{{-- Submit Modal --}}
<div x-data="{
    show: false, taskId: null, taskName: '', taskColor: '', taskSize: '',
    maxQty: 1, qty: 1, defaultRate: 0, rateUsed: 0,
    get earning() { return (this.qty * this.rateUsed).toLocaleString('en-BD', {maximumFractionDigits:0}); },
    open(detail) {
        this.taskId      = detail.taskId;
        this.taskName    = detail.taskName;
        this.taskColor   = detail.taskColor;
        this.taskSize    = detail.taskSize;
        this.maxQty      = detail.max;
        this.qty         = 1;
        this.defaultRate = detail.rate || 0;
        this.rateUsed    = detail.rate || 0;
        this.show        = true;
    }
}" @open-submit-modal.window="open($event.detail)" x-cloak>

    <div x-show="show" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="show = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-2xl p-5 w-full max-w-sm shadow-2xl">
            <button @click="show = false" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 dark:hover:text-slate-300">✕</button>

            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1">কাজ জমা দিন</h3>
            <p class="text-sm font-medium text-gray-700 dark:text-slate-300" x-text="taskName"></p>
            <p class="text-xs text-primary-500 mb-4" x-show="taskColor || taskSize">
                <span x-text="[taskColor, taskSize].filter(Boolean).join(' · ')"></span>
            </p>

            <form method="POST" :action="'{{ url('production/tasks') }}/' + taskId + '/submit'">
                @csrf

                {{-- Quantity --}}
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">
                        পরিমাণ (সর্বোচ্চ <span x-text="maxQty"></span> পিস)
                    </label>
                    <input type="number" name="submitted_quantity" x-model.number="qty"
                           :max="maxQty" min="1" required
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                </div>

                {{-- Rate Override --}}
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">
                        প্রতি পিস রেট (৳)
                        <span x-show="defaultRate > 0" class="text-gray-400 font-normal">
                            — assigned: ৳<span x-text="defaultRate"></span>
                        </span>
                    </label>
                    <input type="number" name="rate_used" x-model.number="rateUsed"
                           min="0" step="0.01"
                           placeholder="0 = no earning"
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <p x-show="defaultRate > 0 && rateUsed != defaultRate"
                       class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                        ⚠️ Assigned rate ৳<span x-text="defaultRate"></span> থেকে আলাদা
                    </p>
                </div>

                {{-- Earning Preview --}}
                <div x-show="rateUsed > 0"
                     class="mb-3 flex items-center justify-between bg-green-50 dark:bg-green-900/20 rounded-lg px-3 py-2">
                    <span class="text-xs text-gray-500 dark:text-slate-400">
                        <span x-text="qty"></span> পিস × ৳<span x-text="rateUsed"></span>
                    </span>
                    <span class="text-sm font-bold text-green-600 dark:text-green-400">
                        = ৳<span x-text="earning"></span>
                    </span>
                </div>

                {{-- Quality note --}}
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">মান নোট (ঐচ্ছিক)</label>
                    <input type="text" name="quality_note" placeholder="যেমন: ভালো মান"
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                </div>

                <button type="submit" class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors">
                    জমা দিন
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Task List --}}
@forelse($tasks as $task)
@php
    $pct = $task->completion_percent;
    $isPending = $task->pending_quantity > 0;
    $statusColors = [
        'assigned'    => 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400',
        'in_progress' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        'partial'     => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        'completed'   => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'cancelled'   => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
    ];
    $statusLabels = [
        'assigned'    => 'নির্ধারিত',
        'in_progress' => 'চলছে',
        'partial'     => 'আংশিক',
        'completed'   => 'সম্পন্ন',
        'cancelled'   => 'বাতিল',
    ];
    $barColor = $task->status === 'completed' ? 'bg-green-500' : ($pct > 50 ? 'bg-primary-500' : 'bg-primary-400');
@endphp

<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-3">
    <div class="flex items-start justify-between gap-3 mb-3">
        <div class="flex-1">
            @php
                $stageLabelBn = match($task->stage ?? 'sewing') {
                    'ironing' => 'ইস্ত্রি',
                    'folding' => 'ভাঁজ',
                    'packing' => 'প্যাকিং',
                    default   => 'সেলাই',
                };
                $stageBadgeBn = match($task->stage ?? 'sewing') {
                    'ironing' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                    'folding' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                    'packing' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
                    default   => 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400',
                };
            @endphp
            <div class="flex items-center gap-1.5 mb-0.5">
                <span class="px-1.5 py-0.5 text-xs rounded font-medium {{ $stageBadgeBn }}">{{ $stageLabelBn }}</span>
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $task->product_name }}</p>
            </div>
            <div class="flex flex-wrap gap-1.5 mt-1">
                @if($task->color)
                <span class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 rounded-full">{{ $task->color }}</span>
                @endif
                @if($task->size)
                <span class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 rounded-full">সাইজ {{ $task->size }}</span>
                @endif
            </div>
        </div>
        <span class="text-xs px-2 py-1 rounded-full font-medium shrink-0 {{ $statusColors[$task->status] ?? 'bg-gray-100 text-gray-500' }}">
            {{ $statusLabels[$task->status] ?? ucfirst($task->status) }}
        </span>
    </div>

    {{-- Progress --}}
    <div class="mb-3">
        <div class="flex justify-between text-xs text-gray-500 dark:text-slate-400 mb-1">
            <span>{{ $task->completed_quantity }} / {{ $task->assigned_quantity }} পিস</span>
            <span class="font-semibold {{ $pct >= 100 ? 'text-green-600' : 'text-gray-700 dark:text-slate-300' }}">{{ $pct }}%</span>
        </div>
        <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-2">
            <div class="{{ $barColor }} h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
        </div>
    </div>

    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-slate-400 mb-3">
        <span>বাকি: <strong class="{{ $isPending ? 'text-orange-600' : 'text-green-600' }} dark:font-semibold">{{ $task->pending_quantity }} পিস</strong></span>
        @if($task->deadline)
        <span>ডেডলাইন: <strong class="{{ $task->isOverdue() && $isPending ? 'text-red-600' : 'text-gray-700 dark:text-slate-200' }}">
            {{ $task->deadline->format('d M') }}{{ $task->isOverdue() && $isPending ? ' ⚠️' : '' }}
        </strong></span>
        @endif
        <span>{{ $task->assigned_at->diffForHumans() }}</span>
    </div>

    @if($task->notes)
    <p class="text-xs text-gray-400 dark:text-slate-500 italic border-t border-gray-100 dark:border-slate-700 pt-2 mb-3">{{ $task->notes }}</p>
    @endif

    @if($isPending && $task->status !== 'cancelled')
    @php $taskRate = $task->productionRate?->rate ?? 0; @endphp
    <button
        @click="$dispatch('open-submit-modal', {
            taskId: {{ $task->id }},
            taskName: '{{ addslashes($task->product_name) }}',
            taskColor: '{{ addslashes($task->color ?? '') }}',
            taskSize: '{{ addslashes($task->size ?? '') }}',
            max: {{ $task->pending_quantity }},
            rate: {{ $taskRate }}
        })"
        class="w-full py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-lg transition-colors">
        কাজ জমা দিন (বাকি {{ $task->pending_quantity }} পিস)
        @if($taskRate > 0)<span class="opacity-75 text-xs"> · ৳{{ number_format($taskRate) }}/পিস</span>@endif
    </button>
    @elseif($task->status === 'completed')
    <div class="text-center py-1.5 text-xs text-green-600 dark:text-green-400 font-semibold">
        ✅ সম্পূর্ণ শেষ হয়েছে
    </div>
    @endif
</div>
@empty
<div class="text-center py-16 text-gray-400 dark:text-slate-500">
    <p class="text-4xl mb-3">📋</p>
    <p class="text-sm font-medium">কোনো কাজ নেই</p>
    <p class="text-xs mt-1">Admin নতুন কাজ assign করলে এখানে দেখা যাবে।</p>
</div>
@endforelse

@endsection
