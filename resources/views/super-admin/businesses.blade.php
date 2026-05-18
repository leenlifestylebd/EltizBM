@extends('super-admin.layout')
@section('title', 'All Businesses')

@section('content')

<div class="mb-6 flex items-center justify-between gap-4 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold text-white">All Businesses</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $businesses->total() }} total</p>
    </div>
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..."
               class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-blue-500 w-64">
        <button type="submit" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white text-sm rounded-xl transition-colors">Search</button>
    </form>
</div>

<div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-5 py-3 text-left">Business</th>
                    <th class="px-5 py-3 text-left">Contact</th>
                    <th class="px-4 py-3 text-center">Users</th>
                    <th class="px-4 py-3 text-center">Products</th>
                    <th class="px-4 py-3 text-center">Orders</th>
                    <th class="px-5 py-3 text-left">Trial / Plan</th>
                    <th class="px-5 py-3 text-left">Created</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse($businesses as $biz)
                <tr class="hover:bg-gray-800/30 transition-colors" x-data="{ extendOpen: false, resetOpen: false }">
                    <td class="px-5 py-3">
                        <p class="font-medium text-white">{{ $biz->name }}</p>
                        <p class="text-xs text-gray-600">ID: {{ $biz->id }}</p>
                    </td>
                    <td class="px-5 py-3">
                        <p class="text-gray-300 text-xs">{{ $biz->email }}</p>
                        <p class="text-gray-600 text-xs">{{ $biz->phone }}</p>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="text-white font-semibold">{{ $biz->users_count }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="text-gray-400">{{ $biz->products_count }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="text-gray-400">{{ $biz->orders_count }}</span>
                    </td>
                    <td class="px-5 py-3">
                        @if($biz->trial_ends_at && $biz->trial_ends_at->isFuture())
                            <span class="text-xs bg-yellow-900/50 text-yellow-400 px-2 py-0.5 rounded-full block w-fit">
                                Trial: {{ $biz->trialDaysLeft() }}d left
                            </span>
                        @elseif($biz->trial_ends_at)
                            <span class="text-xs bg-red-900/50 text-red-400 px-2 py-0.5 rounded-full block w-fit">Trial ended</span>
                        @else
                            <span class="text-xs text-gray-600">No trial</span>
                        @endif
                        <p class="text-xs text-gray-600 mt-0.5">{{ $biz->plan ?? 'basic' }}</p>
                    </td>
                    <td class="px-5 py-3 text-xs text-gray-500">
                        {{ $biz->created_at->format('d M Y') }}
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-2">
                            {{-- Toggle Active --}}
                            <form method="POST" action="{{ route('super-admin.businesses.toggle', $biz) }}">
                                @csrf
                                <button type="submit" title="Toggle active/inactive"
                                        class="px-2 py-1 text-xs bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors">
                                    Toggle
                                </button>
                            </form>

                            {{-- Extend Trial --}}
                            <button @click="extendOpen = !extendOpen"
                                    class="px-2 py-1 text-xs bg-yellow-900/50 hover:bg-yellow-800/50 text-yellow-400 rounded-lg transition-colors">
                                Trial +
                            </button>

                            {{-- Delete --}}
                            <form method="POST" action="{{ route('super-admin.businesses.destroy', $biz) }}"
                                  onsubmit="return confirm('PERMANENTLY delete {{ addslashes($biz->name) }} and ALL its data? This cannot be undone!')">
                                @csrf @method('DELETE')
                                <button class="px-2 py-1 text-xs bg-red-900/40 hover:bg-red-800 text-red-400 rounded-lg transition-colors">
                                    Delete
                                </button>
                            </form>
                        </div>

                        {{-- Extend Trial panel --}}
                        <div x-show="extendOpen" x-cloak class="mt-2">
                            <form method="POST" action="{{ route('super-admin.businesses.extend-trial', $biz) }}"
                                  class="flex gap-2">
                                @csrf
                                <input type="number" name="days" value="30" min="1" max="365"
                                       class="w-16 bg-gray-800 border border-gray-700 rounded-lg px-2 py-1 text-xs text-white focus:outline-none focus:border-yellow-500">
                                <button class="px-2 py-1 text-xs bg-yellow-700 hover:bg-yellow-600 text-white rounded-lg">days</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-gray-600">No businesses found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($businesses->hasPages())
    <div class="px-5 py-4 border-t border-gray-800">
        {{ $businesses->links() }}
    </div>
    @endif
</div>

@endsection
