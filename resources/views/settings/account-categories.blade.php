@extends('layouts.app')
@section('title', 'Account Categories')

@section('content')

<div class="max-w-3xl mx-auto">

    {{-- Tabs (same as all settings pages) --}}
    @php
    $tabs = [
        ['route'=>'settings.general',           'label'=>'Profile'],
        ['route'=>'settings.business',          'label'=>'Business'],
        ['route'=>'settings.user-management',   'label'=>'Users'],
        ['route'=>'settings.statuses',          'label'=>'Statuses'],
        ['route'=>'settings.tags',              'label'=>'Tags'],
        ['route'=>'settings.account-categories','label'=>'Categories'],
        ['route'=>'settings.print-templates',   'label'=>'Print'],
    ];
    @endphp
    <div class="flex gap-1 mb-5 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl overflow-x-auto">
        @foreach($tabs as $tab)
        <a href="{{ route($tab['route']) }}"
           class="flex-shrink-0 px-3 py-2 rounded-lg text-xs font-medium transition-colors whitespace-nowrap
                  {{ request()->routeIs($tab['route']) ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300' }}">
            {{ $tab['label'] }}
        </a>
        @endforeach
    </div>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-400">
        ✓ {{ session('success') }}
    </div>
    @endif
    @if($errors->has('delete'))
    <div class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-400">
        ✗ {{ $errors->first('delete') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- ── EXPENSE CATEGORIES ── --}}
        <div class="space-y-3">

            {{-- Add Expense Category --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-7 h-7 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Expense Categories</h3>
                </div>
                <form method="POST" action="{{ route('settings.account-categories.store') }}" class="flex gap-2">
                    @csrf
                    <input type="hidden" name="type" value="expense">
                    <input type="text" name="name" required maxlength="100"
                           placeholder="e.g. Rent, Utilities, Marketing..."
                           value="{{ old('name') }}"
                           class="flex-1 px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500 placeholder-gray-400">
                    <button type="submit" class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors flex-shrink-0">
                        + Add
                    </button>
                </form>
                @error('name') <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p> @enderror
            </div>

            {{-- Expense List --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700 flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-700 dark:text-slate-300">{{ $expense->count() }} Expense Categories</h4>
                </div>
                @if($expense->isNotEmpty())
                <div class="divide-y divide-gray-50 dark:divide-slate-700">
                    @foreach($expense as $cat)
                    <div class="flex items-center justify-between px-4 py-3 group hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-red-400 flex-shrink-0"></span>
                            <span class="text-sm text-gray-800 dark:text-white">{{ $cat->name }}</span>
                            <span class="text-xs text-gray-400 dark:text-slate-500">({{ $cat->transactions()->count() }} tx)</span>
                        </div>
                        <form method="POST" action="{{ route('settings.account-categories.destroy', $cat) }}"
                              onsubmit="return confirm('Delete \'{{ $cat->name }}\'? Cannot be undone.')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="opacity-0 group-hover:opacity-100 p-1.5 text-gray-300 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="px-4 py-10 text-center">
                    <p class="text-sm text-gray-400 dark:text-slate-500">No expense categories yet</p>
                    <p class="text-xs text-gray-300 dark:text-slate-600 mt-1">Add your first one above</p>
                </div>
                @endif
            </div>
        </div>

        {{-- ── INCOME CATEGORIES ── --}}
        <div class="space-y-3">

            {{-- Add Income Category --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-7 h-7 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Income Categories</h3>
                </div>
                <form method="POST" action="{{ route('settings.account-categories.store') }}" class="flex gap-2">
                    @csrf
                    <input type="hidden" name="type" value="income">
                    <input type="text" name="name" required maxlength="100"
                           placeholder="e.g. Sales, Commission, Refund..."
                           class="flex-1 px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500 placeholder-gray-400">
                    <button type="submit" class="px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-colors flex-shrink-0">
                        + Add
                    </button>
                </form>
            </div>

            {{-- Income List --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
                    <h4 class="text-xs font-semibold text-gray-700 dark:text-slate-300">{{ $income->count() }} Income Categories</h4>
                </div>
                @if($income->isNotEmpty())
                <div class="divide-y divide-gray-50 dark:divide-slate-700">
                    @foreach($income as $cat)
                    <div class="flex items-center justify-between px-4 py-3 group hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-green-400 flex-shrink-0"></span>
                            <span class="text-sm text-gray-800 dark:text-white">{{ $cat->name }}</span>
                            <span class="text-xs text-gray-400 dark:text-slate-500">({{ $cat->transactions()->count() }} tx)</span>
                        </div>
                        <form method="POST" action="{{ route('settings.account-categories.destroy', $cat) }}"
                              onsubmit="return confirm('Delete \'{{ $cat->name }}\'?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="opacity-0 group-hover:opacity-100 p-1.5 text-gray-300 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="px-4 py-10 text-center">
                    <p class="text-sm text-gray-400 dark:text-slate-500">No income categories yet</p>
                    <p class="text-xs text-gray-300 dark:text-slate-600 mt-1">Add your first one above</p>
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- Tip --}}
    <div class="mt-4 px-4 py-3 bg-primary-50 dark:bg-primary-900/20 border border-primary-100 dark:border-primary-900/40 rounded-xl flex items-start gap-2.5">
        <svg class="w-4 h-4 text-primary-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-xs text-primary-700 dark:text-primary-400">
            Categories created here appear in the <strong>Expense</strong> and <strong>Income</strong> forms and in all reports. You can also create a category directly from those forms using the <strong>+ New</strong> button.
        </p>
    </div>

</div>

@endsection
