@extends('layouts.app')
@section('title', 'Tailoring Order – ' . $tailoring->order_number)

@section('content')

@php
    $si = $tailoring->stage_info;
    $colorMap = [
        'gray'   => ['badge' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',   'text' => 'text-gray-600'],
        'blue'   => ['badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', 'text' => 'text-blue-600'],
        'yellow' => ['badge' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300', 'text' => 'text-yellow-600'],
        'orange' => ['badge' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300', 'text' => 'text-orange-600'],
        'green'  => ['badge' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300', 'text' => 'text-green-600'],
        'teal'   => ['badge' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300', 'text' => 'text-teal-600'],
        'red'    => ['badge' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300', 'text' => 'text-red-600'],
    ];
    $badge = $colorMap[$si['color']]['badge'] ?? $colorMap['gray']['badge'];
    $isOverdue = $tailoring->delivery_date && $tailoring->delivery_date->isPast() && !in_array($tailoring->stage, ['delivered','cancelled']);
@endphp

{{-- Header --}}
<div class="flex items-start justify-between mb-4 gap-3">
    <div class="flex items-center gap-3">
        <a href="{{ route('tailoring.index') }}" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <div class="flex items-center gap-2">
                <span class="font-mono text-sm font-bold text-gray-900 dark:text-white">{{ $tailoring->order_number }}</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                    {{ $si['icon'] }} {{ $si['label'] }}
                </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                {{ $tailoring->customer_name }}
                @if($tailoring->customer_phone) · {{ $tailoring->customer_phone }} @endif
                · {{ $tailoring->created_at->format('d M Y') }}
            </p>
        </div>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
        <a href="{{ route('tailoring.edit', $tailoring) }}"
           class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
            ✏️ Edit
        </a>
        <form method="POST" action="{{ route('tailoring.destroy', $tailoring) }}"
              onsubmit="return confirm('Delete this order?')">
            @csrf @method('DELETE')
            <button type="submit" class="px-3 py-1.5 text-xs bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 transition-colors">
                🗑 Delete
            </button>
        </form>
    </div>
</div>

{{-- Flash --}}
@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-400">
    {{ session('success') }}
</div>
@endif

{{-- Stage Progress Bar --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-4">
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-medium text-gray-600 dark:text-slate-400">Production Stage</span>
        <span class="text-xs text-gray-400">{{ $tailoring->stage_progress }}%</span>
    </div>
    <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-2 mb-3">
        <div class="bg-primary-500 h-2 rounded-full transition-all" style="width: {{ $tailoring->stage_progress }}%"></div>
    </div>
    {{-- Stage pills --}}
    <div class="flex items-center gap-1 flex-wrap">
        @foreach(\App\Models\TailoringOrder::STAGE_FLOW as $idx => $s)
        @php
            $sInfo    = \App\Models\TailoringOrder::STAGES[$s];
            $current  = $tailoring->stage === $s;
            $past     = array_search($tailoring->stage, \App\Models\TailoringOrder::STAGE_FLOW) > $idx;
        @endphp
        <div class="flex items-center gap-1">
            <span class="text-xs px-2 py-0.5 rounded-full {{ $current ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300 font-semibold ring-1 ring-primary-400' : ($past ? 'bg-green-50 text-green-600 dark:bg-green-900/20' : 'bg-gray-100 text-gray-400 dark:bg-slate-700 dark:text-slate-500') }}">
                {{ $sInfo['icon'] }} {{ $sInfo['label'] }}
            </span>
            @if(!$loop->last)<span class="text-gray-300 dark:text-slate-600 text-xs">→</span>@endif
        </div>
        @endforeach
    </div>

    {{-- Advance stage / Set stage --}}
    <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-gray-100 dark:border-slate-700">
        @if($tailoring->next_stage)
        <form method="POST" action="{{ route('tailoring.advance-stage', $tailoring) }}">
            @csrf
            <button type="submit"
                    class="px-4 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                ▶ Move to {{ \App\Models\TailoringOrder::STAGES[$tailoring->next_stage]['icon'] }} {{ \App\Models\TailoringOrder::STAGES[$tailoring->next_stage]['label'] }}
            </button>
        </form>
        @endif

        {{-- Quick set stage dropdown --}}
        <form method="POST" action="{{ route('tailoring.set-stage', $tailoring) }}" class="flex items-center gap-2">
            @csrf
            <select name="stage" class="px-2 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none">
                @foreach(\App\Models\TailoringOrder::STAGES as $k => $s)
                <option value="{{ $k }}" {{ $tailoring->stage === $k ? 'selected' : '' }}>{{ $s['icon'] }} {{ $s['label'] }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-2 text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600">
                Set Stage
            </button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- LEFT: Details --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Measurements --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3">📏 Measurements</h3>
            @php
                $fields = [
                    'Height'      => $tailoring->height,
                    'Body Fit'    => $tailoring->body_fit,
                    'Body Loose'  => $tailoring->body_loose,
                    'Shoulder'    => $tailoring->shoulder,
                    'Sleeve'      => $tailoring->sleeve,
                    'Sleeve Open' => $tailoring->sleeve_open,
                    'Neck'        => $tailoring->neck,
                    'Round Gher'  => $tailoring->round_gher,
                ];
                $hasAny = collect($fields)->filter()->isNotEmpty();
            @endphp
            @if($hasAny)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($fields as $label => $value)
                @if($value)
                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg px-3 py-2">
                    <p class="text-xs text-gray-400 dark:text-slate-500">{{ $label }}</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ number_format($value, 1) }}"</p>
                </div>
                @endif
                @endforeach
            </div>
            @else
            <p class="text-xs text-gray-400">No measurements recorded.</p>
            @endif
            @if($tailoring->measurement_note)
            <div class="mt-3 px-3 py-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-100 dark:border-yellow-800 rounded-lg">
                <p class="text-xs font-medium text-yellow-700 dark:text-yellow-400 mb-0.5">Note</p>
                <p class="text-xs text-yellow-700 dark:text-yellow-300">{{ $tailoring->measurement_note }}</p>
            </div>
            @endif
        </div>

        {{-- Fabric & Design --}}
        @if($tailoring->fabric_type || $tailoring->color || $tailoring->design_note)
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3">🧵 Fabric & Design</h3>
            <div class="flex flex-wrap gap-3 mb-2">
                @if($tailoring->fabric_type)
                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg px-3 py-2">
                    <p class="text-xs text-gray-400">Fabric</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $tailoring->fabric_type }}</p>
                </div>
                @endif
                @if($tailoring->color)
                <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg px-3 py-2">
                    <p class="text-xs text-gray-400">Color</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $tailoring->color }}</p>
                </div>
                @endif
            </div>
            @if($tailoring->design_note)
            <div class="px-3 py-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg">
                <p class="text-xs font-medium text-blue-700 dark:text-blue-400 mb-0.5">Design Note</p>
                <p class="text-xs text-blue-700 dark:text-blue-300 whitespace-pre-line">{{ $tailoring->design_note }}</p>
            </div>
            @endif
        </div>
        @endif

        {{-- Production Tasks --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200">⚙️ Assigned Workers</h3>
                @if($tailoring->tasks->isNotEmpty())
                <span class="text-xs text-gray-400">{{ $tailoring->tasks->count() }} task(s)</span>
                @endif
            </div>

            @forelse($tailoring->tasks as $task)
            @php
                $taskStatus = [
                    'assigned'    => 'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300',
                    'in_progress' => 'bg-blue-100 text-blue-700',
                    'partial'     => 'bg-yellow-100 text-yellow-700',
                    'completed'   => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                    'cancelled'   => 'bg-red-100 text-red-700',
                ][$task->status] ?? 'bg-gray-100 text-gray-700';
            @endphp
            <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-slate-700 last:border-0">
                <div>
                    <div class="text-xs font-medium text-gray-800 dark:text-slate-200">
                        {{ $task->employee->name ?? '—' }}
                        <span class="font-normal text-gray-400 ml-1">· {{ $task->product_name }}</span>
                    </div>
                    <div class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                        Assigned: {{ $task->assigned_quantity }} pcs
                        · Done: {{ $task->completed_quantity }}
                        · Pending: {{ $task->pending_quantity }}
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="text-right">
                        <div class="w-16 bg-gray-100 dark:bg-slate-700 rounded-full h-1.5">
                            <div class="bg-primary-500 h-1.5 rounded-full" style="width: {{ $task->completion_percent }}%"></div>
                        </div>
                        <span class="text-xs text-gray-400">{{ $task->completion_percent }}%</span>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $taskStatus }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span>
                    <a href="{{ route('production.tasks.show', $task) }}"
                       class="text-xs text-primary-600 dark:text-primary-400 hover:underline">View</a>
                </div>
            </div>
            @empty
            <p class="text-xs text-gray-400 dark:text-slate-500 text-center py-4">No workers assigned yet.</p>
            @endforelse

            {{-- Assign Worker Form --}}
            <div x-data="{ open: false }" class="mt-3">
                <button @click="open = !open"
                        class="w-full px-3 py-2 text-xs font-medium text-primary-600 dark:text-primary-400 border border-dashed border-primary-300 dark:border-primary-700 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                    + Assign Worker
                </button>

                <div x-show="open" x-cloak x-transition class="mt-3 p-3 bg-gray-50 dark:bg-slate-700/50 rounded-lg">
                    <form method="POST" action="{{ route('tailoring.assign-worker', $tailoring) }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Worker <span class="text-red-400">*</span></label>
                                <select name="employee_id" required
                                        class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">
                                    <option value="">— Select worker —</option>
                                    @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Task Stage <span class="text-red-400">*</span></label>
                                <select name="task_stage" required
                                        class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">
                                    @foreach(['cutting','stitching','finishing','ready'] as $s)
                                    <option value="{{ $s }}" {{ $tailoring->stage === $s ? 'selected' : '' }}>
                                        {{ \App\Models\TailoringOrder::STAGES[$s]['icon'] }} {{ ucfirst($s) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Quantity <span class="text-red-400">*</span></label>
                                <input type="number" name="assigned_quantity" min="1" value="1" required
                                       class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Notes</label>
                                <input type="text" name="notes"
                                       class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none"
                                       placeholder="Optional notes">
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mt-3">
                            <button type="submit"
                                    class="px-4 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                                Assign
                            </button>
                            <button type="button" @click="open = false"
                                    class="px-4 py-2 text-xs text-gray-500 bg-white dark:bg-slate-700 rounded-lg border border-gray-200 dark:border-slate-600 hover:bg-gray-50">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    {{-- RIGHT: Summary --}}
    <div class="space-y-4">

        {{-- Payment Summary --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3">💰 Payment</h3>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-slate-400">Total Price</span>
                    <span class="font-semibold text-gray-900 dark:text-white">৳{{ number_format($tailoring->price, 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-slate-400">Advance Paid</span>
                    <span class="font-semibold text-green-600">৳{{ number_format($tailoring->advance_paid, 0) }}</span>
                </div>
                <div class="h-px bg-gray-100 dark:bg-slate-700"></div>
                <div class="flex justify-between text-sm">
                    <span class="font-medium text-gray-700 dark:text-slate-300">Due Amount</span>
                    <span class="font-bold {{ $tailoring->due_amount > 0 ? 'text-red-500' : 'text-green-500' }}">
                        ৳{{ number_format($tailoring->due_amount, 0) }}
                        @if($tailoring->due_amount <= 0) ✓ @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Delivery Info --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3">📅 Delivery</h3>
            @if($tailoring->delivery_date)
            <div class="flex items-center gap-2">
                <div class="text-2xl">{{ $isOverdue ? '⚠️' : '📅' }}</div>
                <div>
                    <p class="text-sm font-semibold {{ $isOverdue ? 'text-red-500' : 'text-gray-900 dark:text-white' }}">
                        {{ $tailoring->delivery_date->format('d M Y') }}
                    </p>
                    <p class="text-xs {{ $isOverdue ? 'text-red-400' : 'text-gray-400' }}">
                        @if($isOverdue)
                            Overdue by {{ now()->diffInDays($tailoring->delivery_date) }} day(s)
                        @else
                            In {{ now()->diffInDays($tailoring->delivery_date) }} day(s)
                        @endif
                    </p>
                </div>
            </div>
            @else
            <p class="text-xs text-gray-400">No delivery date set.</p>
            @endif
        </div>

        {{-- Meta --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3">ℹ️ Info</h3>
            <div class="space-y-2 text-xs text-gray-500 dark:text-slate-400">
                <div class="flex justify-between">
                    <span>Created by</span>
                    <span class="text-gray-700 dark:text-slate-300">{{ $tailoring->createdBy?->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Created at</span>
                    <span class="text-gray-700 dark:text-slate-300">{{ $tailoring->created_at->format('d M Y, h:i A') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Last updated</span>
                    <span class="text-gray-700 dark:text-slate-300">{{ $tailoring->updated_at->diffForHumans() }}</span>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection
