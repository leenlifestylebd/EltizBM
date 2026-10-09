@extends('layouts.app')
@section('title', 'Account Balance')

@section('content')

<div class="mb-5">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Account Balance</h2>
    <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Balance across all payment methods{{ ($from || $to) ? ' · '.($from ?: 'start').' → '.($to ?: 'today') : ' (all time)' }}</p>
</div>

<form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs font-medium text-gray-600 dark:text-slate-300 mb-1">From</label>
        <input type="date" name="from" value="{{ $from }}" class="px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 dark:text-slate-300 mb-1">To</label>
        <input type="date" name="to" value="{{ $to }}" class="px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
    </div>
    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">Filter</button>
    @if($from || $to)
    <a href="{{ route('reports.account-balance') }}" class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-slate-300 hover:underline">Reset</a>
    @endif
</form>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-5">
    @php $colors=['cash'=>'green','bkash'=>'pink','nagad'=>'orange','rocket'=>'purple','bank'=>'blue','card'=>'indigo']; @endphp
    @foreach($balances as $method => $b)
    @php $c = $colors[$method] ?? 'gray'; @endphp
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-{{ $c }}-100 dark:border-{{ $c }}-900/30 p-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $b['method'] }}</p>
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $b['balance']>=0?'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400':'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                {{ $b['balance']>=0?'Credit':'Debit' }}
            </span>
        </div>
        <p class="text-2xl font-bold {{ $b['balance']>=0?'text-'.$c.'-600 dark:text-'.$c.'-400':'text-red-600 dark:text-red-400' }}">
            ৳{{ number_format(abs($b['balance'])) }}
        </p>
        <div class="mt-3 space-y-1 text-xs text-gray-500 dark:text-slate-400">
            <div class="flex justify-between"><span>Opening</span><span>৳{{ number_format($b['opening'] ?? 0) }}</span></div>
            <div class="flex justify-between"><span>Income</span><span class="text-green-600 dark:text-green-400">+৳{{ number_format($b['income']) }}</span></div>
            <div class="flex justify-between"><span>Expense</span><span class="text-red-500">-৳{{ number_format($b['expense']) }}</span></div>
        </div>
    </div>
    @endforeach
</div>

@if(in_array(auth()->user()->role, ['owner','admin'], true))
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-5">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Opening Balances</h3>
    <form method="POST" action="{{ route('reports.account-balance.opening') }}">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach($balances as $method => $b)
            <div>
                <label class="block text-xs text-gray-500 dark:text-slate-400 mb-1">{{ $b['method'] }}</label>
                <input type="number" step="0.01" name="opening[{{ $method }}]" value="{{ $b['opening'] ?? 0 }}"
                       class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
            </div>
            @endforeach
        </div>
        <button type="submit" class="mt-3 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">Save Opening Balances</button>
    </form>
</div>
@endif

{{-- Total Balance --}}
@php $totalBalance = collect($balances)->sum('balance'); @endphp
<div class="bg-gradient-to-r {{ $totalBalance>=0?'from-green-500 to-emerald-600':'from-red-500 to-rose-600' }} rounded-xl p-5 text-white">
    <p class="text-white/80 text-sm">Total Net Balance (All Accounts)</p>
    <p class="text-3xl font-bold mt-1">৳{{ number_format(abs($totalBalance)) }}</p>
    <p class="text-white/80 text-sm mt-0.5">{{ $totalBalance>=0?'You are in profit across all accounts':'You have a net deficit' }}</p>
</div>

@endsection