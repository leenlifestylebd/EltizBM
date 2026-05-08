@extends('layouts.app')
@section('title', 'Subscription')

@section('content')

<div class="max-w-3xl mx-auto">

    <div class="flex gap-1 mb-5 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl overflow-x-auto scrollbar-hide">
        @foreach([['route'=>'settings.general','label'=>'Profile'],['route'=>'settings.business','label'=>'Business'],['route'=>'settings.user-management','label'=>'Users'],['route'=>'settings.subscription','label'=>'Subscription'],['route'=>'settings.warehouses','label'=>'Warehouses'],['route'=>'settings.print-templates','label'=>'Print']] as $tab)
        <a href="{{ route($tab['route']) }}" class="flex-shrink-0 px-3 py-2 rounded-lg text-xs font-medium transition-colors whitespace-nowrap {{ request()->routeIs($tab['route']) ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300' }}">{{ $tab['label'] }}</a>
        @endforeach
    </div>

    {{-- Current Plan --}}
    <div class="bg-gradient-to-r from-blue-500 to-blue-700 rounded-2xl p-6 text-white mb-4">
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <span class="font-bold text-lg">{{ ucfirst($business->plan) }} Plan</span>
                </div>
                @if($business->isOnTrial())
                    <p class="text-blue-100 text-sm">⏰ {{ $business->trialDaysLeft() }} days left in trial</p>
                @else
                    <p class="text-blue-100 text-sm">Active subscription</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-blue-100 text-xs">Business ID</p>
                <p class="font-mono font-bold">BIZ-{{ str_pad($business->id,6,'0',STR_PAD_LEFT) }}</p>
            </div>
        </div>
    </div>

    {{-- Plan Comparison --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
        @foreach([
            ['name'=>'Basic','price'=>'Free','period'=>'14-day trial','features'=>['500 orders/month','100 products','2 users','Basic reports','POS included'],'current'=>$business->plan==='basic','color'=>'gray'],
            ['name'=>'Pro','price'=>'৳999','period'=>'/month','features'=>['Unlimited orders','Unlimited products','10 users','All reports','WooCommerce sync','SMS notifications'],'current'=>$business->plan==='pro','color'=>'blue','popular'=>true],
            ['name'=>'Business','price'=>'৳2499','period'=>'/month','features'=>['Everything in Pro','Unlimited users','API access','Priority support','Custom domain','White label'],'current'=>$business->plan==='business','color'=>'purple'],
        ] as $plan)
        <div class="bg-white dark:bg-slate-800 rounded-xl border {{ isset($plan['popular']) ? 'border-blue-400 dark:border-blue-500 shadow-md' : 'border-gray-100 dark:border-slate-700' }} p-4 relative">
            @if(isset($plan['popular']))
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-xs font-medium px-3 py-0.5 rounded-full">Most Popular</span>
            @endif
            @if($plan['current'])
                <span class="absolute top-3 right-3 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-xs font-medium px-2 py-0.5 rounded-full">Current</span>
            @endif
            <h3 class="font-bold text-gray-900 dark:text-white mb-1">{{ $plan['name'] }}</h3>
            <p class="text-2xl font-black text-{{ $plan['color'] }}-600 dark:text-{{ $plan['color'] }}-400">{{ $plan['price'] }}<span class="text-xs font-normal text-gray-400">{{ $plan['period'] }}</span></p>
            <ul class="mt-3 space-y-1.5">
                @foreach($plan['features'] as $f)
                <li class="text-xs text-gray-600 dark:text-slate-300 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ $f }}
                </li>
                @endforeach
            </ul>
            @if(!$plan['current'])
            <button class="w-full mt-4 py-2 text-xs font-medium bg-{{ $plan['color'] }}-600 text-white rounded-lg hover:bg-{{ $plan['color'] }}-700 transition-colors">
                Upgrade to {{ $plan['name'] }}
            </button>
            @endif
        </div>
        @endforeach
    </div>

</div>

@endsection


{{-- ============================================================
     FILE: resources/views/settings/print-templates.blade.php
     ============================================================ --}}