@extends('layouts.app')
@section('title', 'Warehouses')

@section('content')

<div class="max-w-3xl mx-auto">

    <div class="flex gap-1 mb-5 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl overflow-x-auto scrollbar-hide">
        @foreach([['route'=>'settings.general','label'=>'Profile'],['route'=>'settings.business','label'=>'Business'],['route'=>'settings.user-management','label'=>'Users'],['route'=>'settings.warehouses','label'=>'Warehouses'],['route'=>'settings.statuses','label'=>'Statuses'],['route'=>'settings.tags','label'=>'Tags'],['route'=>'settings.print-templates','label'=>'Print'],['route'=>'settings.account-categories','label'=>'Categories']] as $tab)
        <a href="{{ route($tab['route']) }}" class="flex-shrink-0 px-3 py-2 rounded-lg text-xs font-medium transition-colors whitespace-nowrap {{ request()->routeIs($tab['route']) ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300' }}">{{ $tab['label'] }}</a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 h-fit">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Add Warehouse</h3>
            <form method="POST" action="{{ route('settings.warehouses.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="e.g. Main Warehouse, Branch 2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Address</label>
                    <textarea name="address" rows="2" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_default" id="is_default" class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                    <label for="is_default" class="text-xs text-gray-600 dark:text-slate-300">Set as default warehouse</label>
                </div>
                <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">Add Warehouse</button>
            </form>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Your Warehouses</h3>
            </div>
            <div class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($warehouses as $wh)
                <div class="flex items-center gap-3 px-4 py-3">
                    <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $wh->name }}</p>
                            @if($wh->is_default)
                                <span class="text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-1.5 py-0.5 rounded">Default</span>
                            @endif
                        </div>
                        @if($wh->address)
                            <p class="text-xs text-gray-400 dark:text-slate-500 truncate">{{ $wh->address }}</p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('settings.warehouses.destroy', $wh) }}" onsubmit="return confirm('Delete this warehouse?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors flex-shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
                @empty
                <div class="px-4 py-8 text-center text-sm text-gray-400 dark:text-slate-500">No warehouses yet</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection