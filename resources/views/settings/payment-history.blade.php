@extends('layouts.app')
@section('title', 'Payment History')

@section('content')

<div class="max-w-3xl mx-auto">

    <div class="flex gap-1 mb-5 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl overflow-x-auto">
        @foreach([['route'=>'settings.general','label'=>'Profile'],['route'=>'settings.business','label'=>'Business'],['route'=>'settings.user-management','label'=>'Users'],['route'=>'settings.warehouses','label'=>'Warehouses'],['route'=>'settings.print-templates','label'=>'Print']] as $tab)
        <a href="{{ route($tab['route']) }}" class="flex-shrink-0 px-3 py-2 rounded-lg text-xs font-medium transition-colors whitespace-nowrap {{ request()->routeIs($tab['route']) ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300' }}">{{ $tab['label'] }}</a>
        @endforeach
    </div>

    {{-- Current Plan Banner --}}
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-xl p-5 mb-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs opacity-80 mb-1">Current Plan</p>
                <h3 class="text-lg font-bold capitalize">{{ $business->plan ?? 'Basic' }}</h3>
                @if($business->trial_ends_at)
                    <p class="text-xs opacity-80 mt-1">
                        @if($business->trial_ends_at->isFuture())
                            Trial ends {{ $business->trial_ends_at->diffForHumans() }}
                        @else
                            Trial ended {{ $business->trial_ends_at->diffForHumans() }}
                        @endif
                    </p>
                @endif
            </div>
            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
        </div>
    </div>

    {{-- Payment History Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-50 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Billing History</h3>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Your subscription payment records</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Date</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Description</th>
                        <th class="text-right px-5 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Amount</th>
                        <th class="text-center px-5 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-12 h-12 bg-gray-100 dark:bg-slate-700 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-slate-400">No payment records found</p>
                                <p class="text-xs text-gray-400 dark:text-slate-500">Payment history will appear here once you upgrade to a paid plan</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Upgrade CTA --}}
    <div class="mt-4 bg-primary-50 dark:bg-primary-900/20 border border-primary-100 dark:border-primary-800 rounded-xl p-4 flex items-center justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-primary-900 dark:text-primary-200">Upgrade to Pro</p>
            <p class="text-xs text-primary-600 dark:text-primary-400 mt-0.5">Get unlimited orders, advanced reports, and priority support</p>
        </div>
        <button class="flex-shrink-0 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
            Upgrade Now
        </button>
    </div>

</div>

@endsection

