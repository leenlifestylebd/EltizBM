@extends('layouts.app')
@section('title', 'Tailoring Orders')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Tailoring Orders</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Custom garment orders & production tracking</p>
    </div>
    <a href="{{ route('tailoring.create') }}"
       class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
        + New Order
    </a>
</div>

{{-- Stage Stats Cards --}}
<div class="grid grid-cols-3 md:grid-cols-7 gap-2 mb-4">
    @php
        $stageColors = [
            'pending'   => 'gray',
            'cutting'   => 'blue',
            'stitching' => 'yellow',
            'finishing' => 'orange',
            'ready'     => 'green',
            'delivered' => 'teal',
            'cancelled' => 'red',
        ];
        $stageIcons = [
            'pending'   => '📋',
            'cutting'   => '✂️',
            'stitching' => '🪡',
            'finishing' => '🔧',
            'ready'     => '✅',
            'delivered' => '📦',
            'cancelled' => '❌',
        ];
        $colorMap = [
            'gray'   => 'border-gray-200   dark:border-gray-700   text-gray-600   dark:text-gray-400',
            'blue'   => 'border-blue-200   dark:border-blue-800   text-blue-600   dark:text-blue-400',
            'yellow' => 'border-yellow-200 dark:border-yellow-800 text-yellow-600 dark:text-yellow-400',
            'orange' => 'border-orange-200 dark:border-orange-800 text-orange-600 dark:text-orange-400',
            'green'  => 'border-green-200  dark:border-green-800  text-green-600  dark:text-green-400',
            'teal'   => 'border-teal-200   dark:border-teal-800   text-teal-600   dark:text-teal-400',
            'red'    => 'border-red-200    dark:border-red-800    text-red-600    dark:text-red-400',
        ];
    @endphp
    @foreach(\App\Models\TailoringOrder::STAGES as $stageKey => $stageInfo)
    @php $col = $colorMap[$stageInfo['color']] ?? $colorMap['gray']; @endphp
    <a href="{{ request()->fullUrlWithQuery(['stage' => $stageKey]) }}"
       class="bg-white dark:bg-slate-800 rounded-xl p-2.5 border {{ $col }} hover:shadow-sm transition-shadow text-center {{ request('stage') === $stageKey ? 'ring-2 ring-primary-400' : '' }}">
        <div class="text-sm mb-0.5">{{ $stageInfo['icon'] }}</div>
        <div class="text-lg font-bold {{ $col }}">{{ $stageCounts[$stageKey] ?? 0 }}</div>
        <div class="text-[10px] text-gray-500 dark:text-slate-400 leading-tight">{{ $stageInfo['label'] }}</div>
    </a>
    @endforeach
</div>

{{-- Search + Filter --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Order #, name, phone…"
                   class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-400">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Stage</label>
            <select name="stage" onchange="this.form.submit()"
                    class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none">
                <option value="">All Stages</option>
                @foreach(\App\Models\TailoringOrder::STAGES as $k => $s)
                <option value="{{ $k }}" {{ request('stage') === $k ? 'selected' : '' }}>{{ $s['icon'] }} {{ $s['label'] }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-3 py-2 bg-primary-600 text-white text-xs rounded-lg hover:bg-primary-700">Search</button>
        @if(request()->hasAny(['search','stage']))
        <a href="{{ route('tailoring.index') }}" class="px-3 py-2 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-xs rounded-lg">Clear</a>
        @endif
    </form>
</div>

{{-- Flash messages --}}
@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-400">
    {{ session('success') }}
</div>
@endif

{{-- Desktop Table --}}
<div class="hidden md:block bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden mb-4">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400">Order #</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400">Stage</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400">Price</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400">Due</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400">Delivery</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400">Date</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-slate-400">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($orders as $order)
                @php
                    $si  = $order->stage_info;
                    $col = $colorMap[$si['color']] ?? $colorMap['gray'];
                    $isOverdue = $order->delivery_date && $order->delivery_date->isPast() && !in_array($order->stage, ['delivered','cancelled']);
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/40 transition-colors">
                    <td class="px-4 py-3">
                        <a href="{{ route('tailoring.show', $order) }}" class="font-mono text-xs text-primary-600 dark:text-primary-400 hover:underline">
                            {{ $order->order_number }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-xs font-medium text-gray-900 dark:text-white">{{ $order->customer_name }}</div>
                        @if($order->customer_phone)
                        <div class="text-xs text-gray-400 dark:text-slate-500">{{ $order->customer_phone }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $col }} bg-opacity-10">
                            {{ $si['icon'] }} {{ $si['label'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-700 dark:text-slate-300">
                        ৳{{ number_format($order->price, 0) }}
                    </td>
                    <td class="px-4 py-3 text-xs {{ $order->due_amount > 0 ? 'text-red-500 dark:text-red-400 font-medium' : 'text-green-600 dark:text-green-400' }}">
                        @if($order->due_amount > 0)
                            ৳{{ number_format($order->due_amount, 0) }}
                        @else
                            <span class="text-green-500">✓ Paid</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs {{ $isOverdue ? 'text-red-500 dark:text-red-400 font-medium' : 'text-gray-500 dark:text-slate-400' }}">
                        {{ $order->delivery_date ? $order->delivery_date->format('d M') : '—' }}
                        @if($isOverdue) <span class="text-red-400">⚠</span> @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400 dark:text-slate-500">
                        {{ $order->created_at->format('d M, y') }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('tailoring.show', $order) }}"
                           class="inline-flex items-center px-2.5 py-1 text-xs bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/50">
                            View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">
                        No tailoring orders found.
                        <a href="{{ route('tailoring.create') }}" class="text-primary-600 hover:underline ml-1">Create first order →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Mobile Cards --}}
<div class="md:hidden space-y-3 mb-4">
    @forelse($orders as $order)
    @php
        $si = $order->stage_info;
        $col = $colorMap[$si['color']] ?? $colorMap['gray'];
        $isOverdue = $order->delivery_date && $order->delivery_date->isPast() && !in_array($order->stage, ['delivered','cancelled']);
    @endphp
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
        <div class="flex items-start justify-between mb-2">
            <div>
                <a href="{{ route('tailoring.show', $order) }}" class="font-mono text-xs text-primary-600 dark:text-primary-400 font-semibold">{{ $order->order_number }}</a>
                <div class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ $order->customer_name }}</div>
                @if($order->customer_phone)<div class="text-xs text-gray-400">{{ $order->customer_phone }}</div>@endif
            </div>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $col }}">
                {{ $si['icon'] }} {{ $si['label'] }}
            </span>
        </div>

        {{-- Stage progress bar --}}
        @php $progress = $order->stage_progress; @endphp
        <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-1.5 mb-2">
            <div class="bg-primary-500 h-1.5 rounded-full" style="width: {{ $progress }}%"></div>
        </div>

        <div class="flex items-center justify-between text-xs">
            <div class="text-gray-500 dark:text-slate-400">
                ৳{{ number_format($order->price, 0) }}
                @if($order->due_amount > 0)
                <span class="text-red-500 ml-1">Due: ৳{{ number_format($order->due_amount, 0) }}</span>
                @endif
            </div>
            <div class="{{ $isOverdue ? 'text-red-500' : 'text-gray-400 dark:text-slate-500' }}">
                {{ $order->delivery_date ? $order->delivery_date->format('d M') : '—' }}
                @if($isOverdue) ⚠ @endif
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-10 text-sm text-gray-400">
        No orders.
        <a href="{{ route('tailoring.create') }}" class="text-primary-600">Create one →</a>
    </div>
    @endforelse
</div>

{{-- Pagination --}}
<div>{{ $orders->links() }}</div>

@endsection
