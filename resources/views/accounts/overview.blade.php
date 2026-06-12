@extends('layouts.app')
@section('title', 'Accounts Overview')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Accounts</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Financial overview</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('accounts.expenses') }}"
           class="px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 transition-colors">
            + Expense
        </a>
        <a href="{{ route('accounts.income') }}"
           class="px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
            + Income
        </a>
    </div>
</div>

{{-- Period Tabs --}}
<div class="flex gap-1.5 mb-4 overflow-x-auto pb-1 scrollbar-hide">
    @foreach(['today'=>'Today','week'=>'This Week','month'=>'This Month','year'=>'This Year'] as $key => $label)
    <a href="{{ route('accounts.overview', ['period' => $key]) }}"
       class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap
              {{ ($period ?? 'month') === $key ? 'bg-primary-600 text-white' : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-slate-300 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-green-100 dark:border-green-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Total Income</p>
        <p class="text-xl font-bold text-green-600 dark:text-green-400">৳{{ number_format($summary['income']) }}</p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $summary['income_count'] }} entries</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-red-100 dark:border-red-900/30">
        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Total Expenses</p>
        <p class="text-xl font-bold text-red-600 dark:text-red-400">৳{{ number_format($summary['expense']) }}</p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $summary['expense_count'] }} entries</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border {{ $summary['profit'] >= 0 ? 'border-primary-100 dark:border-primary-900/30' : 'border-orange-100 dark:border-orange-900/30' }}">
        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Net Profit</p>
        <p class="text-xl font-bold {{ $summary['profit'] >= 0 ? 'text-primary-600 dark:text-primary-400' : 'text-orange-600 dark:text-orange-400' }}">
            ৳{{ number_format(abs($summary['profit'])) }}
        </p>
        <p class="text-xs mt-0.5 {{ $summary['profit'] >= 0 ? 'text-green-500' : 'text-red-500' }}">
            {{ $summary['profit'] >= 0 ? '↑ Profit' : '↓ Loss' }}
        </p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-700">
        <p class="text-xs text-gray-500 dark:text-slate-400 mb-1">Transactions</p>
        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $summary['count'] }}</p>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">total entries</p>
    </div>
</div>

{{-- Bar Chart --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 mb-4">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Monthly Overview (Last 6 Months)</h3>
    @php $maxVal = collect($monthlyData)->max(fn($x) => max($x['income'], $x['expense'])) ?: 1; @endphp
    <div class="flex items-end gap-2 h-28">
        @foreach($monthlyData as $m)
        <div class="flex-1 flex flex-col items-center gap-1">
            <div class="w-full flex flex-col gap-0.5" style="height:88px; justify-content:flex-end; display:flex; flex-direction:column;">
                <div class="w-full rounded-t-sm bg-green-400 dark:bg-green-500" style="height:{{ max(2, ($m['income']/$maxVal)*80) }}px" title="Income: ৳{{ number_format($m['income']) }}"></div>
                <div class="w-full rounded-t-sm bg-red-400 dark:bg-red-500 mt-0.5" style="height:{{ max(2, ($m['expense']/$maxVal)*80) }}px" title="Expense: ৳{{ number_format($m['expense']) }}"></div>
            </div>
            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $m['label'] }}</p>
        </div>
        @endforeach
    </div>
    <div class="flex gap-4 mt-3">
        <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-slate-400"><span class="w-3 h-3 bg-green-400 rounded"></span>Income</span>
        <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-slate-400"><span class="w-3 h-3 bg-red-400 rounded"></span>Expense</span>
    </div>
</div>

{{-- Quick Nav --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
    @foreach([
        ['route'=>'accounts.income',          'label'=>'Income',    'color'=>'green',  'icon'=>'M5 10l7-7m0 0l7 7m-7-7v18'],
        ['route'=>'accounts.expenses',        'label'=>'Expenses',  'color'=>'red',    'icon'=>'M19 14l-7 7m0 0l-7-7m7 7V3'],
        ['route'=>'production.payroll',       'label'=>'Salary',    'color'=>'primary',   'icon'=>'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
        ['route'=>'accounts.payment-transfer','label'=>'Transfers', 'color'=>'purple', 'icon'=>'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
    ] as $nav)
    <a href="{{ route($nav['route']) }}"
       class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 hover:shadow-sm transition-all">
        <div class="w-9 h-9 bg-{{ $nav['color'] }}-100 dark:bg-{{ $nav['color'] }}-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-{{ $nav['color'] }}-600 dark:text-{{ $nav['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $nav['icon'] }}"/>
            </svg>
        </div>
        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">{{ $nav['label'] }}</span>
    </a>
    @endforeach
</div>

{{-- Recent Transactions --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-50 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Transactions</h3>
        <a href="{{ route('accounts.income') }}" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">View all</a>
    </div>
    <div class="divide-y divide-gray-50 dark:divide-slate-700">
        @forelse($recentTransactions as $txn)
        <div class="flex items-center gap-3 px-4 py-3">
            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                {{ $txn->type === 'income' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }}">
                <svg class="w-4 h-4 {{ $txn->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="{{ $txn->type === 'income' ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3' }}"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $txn->title }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                    {{ $txn->category?->name ?? '—' }} · {{ $txn->transaction_date->format('d M') }} · {{ ucfirst($txn->payment_method) }}
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="text-sm font-bold {{ $txn->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $txn->type === 'income' ? '+' : '-' }}৳{{ number_format($txn->amount) }}
                </span>
                <form method="POST" action="{{ route('accounts.transactions.destroy', $txn) }}"
                      onsubmit="return confirm('Delete this transaction?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="p-1 text-gray-300 hover:text-red-500 dark:hover:text-red-400 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="px-4 py-8 text-center text-sm text-gray-400 dark:text-slate-500">No transactions yet</div>
        @endforelse
    </div>
</div>

@endsection
