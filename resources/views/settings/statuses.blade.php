@extends('layouts.app')
@section('title', 'Order Statuses')

@section('content')

<div class="max-w-3xl mx-auto">

    <div class="flex gap-1 mb-5 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl overflow-x-auto">
        @foreach([['route'=>'settings.general','label'=>'Profile'],['route'=>'settings.business','label'=>'Business'],['route'=>'settings.user-management','label'=>'Users'],['route'=>'settings.statuses','label'=>'Statuses'],['route'=>'settings.tags','label'=>'Tags'],['route'=>'settings.print-templates','label'=>'Print'],['route'=>'settings.account-categories','label'=>'Categories']] as $tab)
        <a href="{{ route($tab['route']) }}" class="flex-shrink-0 px-3 py-2 rounded-lg text-xs font-medium transition-colors whitespace-nowrap {{ request()->routeIs($tab['route']) ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300' }}">{{ $tab['label'] }}</a>
        @endforeach
    </div>

    @php
    $defaultStatuses = [
        'pending'    => ['label' => 'Pending',    'color' => '#f59e0b'],
        'confirmed'  => ['label' => 'Confirmed',  'color' => '#3b82f6'],
        'processing' => ['label' => 'Processing', 'color' => '#6366f1'],
        'shipped'    => ['label' => 'Shipped',    'color' => '#8b5cf6'],
        'delivered'  => ['label' => 'Delivered',  'color' => '#10b981'],
        'completed'  => ['label' => 'Completed',  'color' => '#10b981'],
        'cancelled'  => ['label' => 'Cancelled',  'color' => '#ef4444'],
        'returned'   => ['label' => 'Returned',   'color' => '#f97316'],
    ];
    @endphp

    <form method="POST" action="{{ route('settings.statuses.update') }}">
        @csrf
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden mb-4">
            <div class="px-5 py-4 border-b border-gray-50 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Order Status Labels & Colors</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Customize how statuses appear across the system</p>
            </div>
            <div class="divide-y divide-gray-50 dark:divide-slate-700">
                @foreach($defaultStatuses as $key => $default)
                @php
                    $current = $statusConfig[$key] ?? $default;
                @endphp
                <div class="flex items-center gap-4 px-5 py-3.5">
                    <div class="w-8 h-8 rounded-lg flex-shrink-0" style="background-color: {{ $current['color'] }}20; border: 2px solid {{ $current['color'] }}">
                        <div class="w-full h-full rounded-md"></div>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Key: <code class="text-xs bg-gray-100 dark:bg-slate-700 px-1 rounded">{{ $key }}</code></p>
                        <input type="text" name="statuses[{{ $key }}][label]" value="{{ $current['label'] }}"
                               class="w-full px-2.5 py-1.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <input type="color" name="statuses[{{ $key }}][color]" value="{{ $current['color'] }}"
                               class="w-8 h-8 rounded cursor-pointer border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 p-0.5">
                        <span class="text-xs text-gray-400 hidden sm:block">Color</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium text-sm rounded-lg transition-colors">
                Save Status Settings
            </button>
        </div>
    </form>

</div>

@endsection
