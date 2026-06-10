@extends('super-admin.layout')
@section('title', 'Dashboard')

@section('content')

{{-- Header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-white">Super Admin Dashboard</h1>
    <p class="text-sm text-gray-500 mt-1">All businesses, users, and system health</p>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
    @php
    $cards = [
        ['label'=>'Total Businesses', 'value'=>$stats['total_businesses'], 'icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'color'=>'blue'],
        ['label'=>'Active Trials',    'value'=>$stats['trial_businesses'],  'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'yellow'],
        ['label'=>'Total Users',      'value'=>$stats['total_users'],       'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0', 'color'=>'green'],
        ['label'=>'Pending Approval', 'value'=>$stats['pending_users'],     'icon'=>'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'color'=>'red'],
    ];
    $colorMap = ['blue'=>'bg-blue-900/40 text-blue-400','yellow'=>'bg-yellow-900/40 text-yellow-400','green'=>'bg-green-900/40 text-green-400','red'=>'bg-red-900/40 text-red-400'];
    @endphp

    @foreach($cards as $card)
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-8 h-8 {{ $colorMap[$card['color']] }} rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                </svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-white">{{ $card['value'] }}</p>
        <p class="text-xs text-gray-500 mt-0.5">{{ $card['label'] }}</p>
    </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-6">

    {{-- Recent Businesses --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between">
            <h2 class="font-semibold text-white text-sm">Recent Businesses</h2>
            <a href="{{ route('super-admin.businesses') }}" class="text-xs text-blue-400 hover:text-blue-300">View all →</a>
        </div>
        <div class="divide-y divide-gray-800">
            @forelse($recentBusinesses as $biz)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-white">{{ $biz->name }}</p>
                    <p class="text-xs text-gray-500">{{ $biz->email }} · {{ $biz->users_count }} users</p>
                </div>
                <div class="text-right">
                    @if($biz->trial_ends_at && $biz->trial_ends_at->isFuture())
                        <span class="text-xs bg-yellow-900/50 text-yellow-400 px-2 py-0.5 rounded-full">
                            Trial {{ $biz->trial_ends_at->diffForHumans() }}
                        </span>
                    @else
                        <span class="text-xs bg-green-900/50 text-green-400 px-2 py-0.5 rounded-full">Active</span>
                    @endif
                    <p class="text-xs text-gray-600 mt-0.5">{{ $biz->created_at->format('d M Y') }}</p>
                </div>
            </div>
            @empty
            <p class="px-5 py-6 text-sm text-gray-600 text-center">No businesses yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Pending Users --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between">
            <h2 class="font-semibold text-white text-sm">
                Pending Approvals
                @if($pendingUsers->count() > 0)
                <span class="ml-1.5 bg-red-600 text-white text-xs px-1.5 py-0.5 rounded-full">{{ $pendingUsers->count() }}</span>
                @endif
            </h2>
            <a href="{{ route('super-admin.users', ['status'=>'pending']) }}" class="text-xs text-blue-400 hover:text-blue-300">View all →</a>
        </div>
        <div class="divide-y divide-gray-800">
            @forelse($pendingUsers as $user)
            <div class="px-5 py-3 flex items-center justify-between gap-3" x-data>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                    <p class="text-xs text-gray-600">{{ $user->business?->name }}</p>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <form method="POST" action="{{ route('super-admin.users.approve', $user) }}">
                        @csrf
                        <button class="px-3 py-1 text-xs bg-green-700 hover:bg-green-600 text-white rounded-lg transition-colors">
                            Approve
                        </button>
                    </form>
                    <form method="POST" action="{{ route('super-admin.users.reject', $user) }}"
                          onsubmit="return confirm('Reject and delete {{ $user->name }}?')">
                        @csrf @method('DELETE')
                        <button class="px-3 py-1 text-xs bg-red-900 hover:bg-red-700 text-red-300 rounded-lg transition-colors">
                            Reject
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="px-5 py-8 text-center">
                <svg class="w-8 h-8 text-gray-700 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-gray-600">No pending approvals</p>
            </div>
            @endforelse
        </div>
    </div>

</div>

{{-- Create Business Form --}}
<div class="mt-6 bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden" x-data="{ open: false }">
    <button @click="open = !open" class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-gray-800/50 transition-colors">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="font-semibold text-white text-sm">Create New Business</span>
        </div>
        <svg class="w-4 h-4 text-gray-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" x-cloak class="px-5 pb-5 border-t border-gray-800">
        <form method="POST" action="{{ route('super-admin.businesses.store') }}" class="mt-4 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-400 mb-1">Business Name *</label>
                <input type="text" name="business_name" required placeholder="ABC Garments"
                       class="w-full bg-gray-800 border border-gray-700 rounded-xl px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Owner Name *</label>
                <input type="text" name="owner_name" required placeholder="John Doe"
                       class="w-full bg-gray-800 border border-gray-700 rounded-xl px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Email *</label>
                <input type="email" name="email" required placeholder="owner@business.com"
                       class="w-full bg-gray-800 border border-gray-700 rounded-xl px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Password *</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full bg-gray-800 border border-gray-700 rounded-xl px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Trial Days</label>
                <input type="number" name="trial_days" value="14" min="1" max="365"
                       class="w-full bg-gray-800 border border-gray-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex items-end">
                <button type="submit"
                        class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
                    Create Business
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
