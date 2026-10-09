@extends('layouts.app')
@section('title', 'Tags')

@section('content')

<div class="max-w-3xl mx-auto">

    <div class="flex gap-1 mb-5 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl overflow-x-auto">
        @foreach([['route'=>'settings.general','label'=>'Profile'],['route'=>'settings.business','label'=>'Business'],['route'=>'settings.user-management','label'=>'Users'],['route'=>'settings.statuses','label'=>'Statuses'],['route'=>'settings.tags','label'=>'Tags'],['route'=>'settings.print-templates','label'=>'Print'],['route'=>'settings.account-categories','label'=>'Categories']] as $tab)
        <a href="{{ route($tab['route']) }}" class="flex-shrink-0 px-3 py-2 rounded-lg text-xs font-medium transition-colors whitespace-nowrap {{ request()->routeIs($tab['route']) ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300' }}">{{ $tab['label'] }}</a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Add Tag Form --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 h-fit">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Add New Tag</h3>
            <form method="POST" action="{{ route('settings.tags.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Tag Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required maxlength="50"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                           placeholder="e.g. VIP, Bulk Order, Priority">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="color" value="#3b82f6"
                               class="w-10 h-10 rounded-lg cursor-pointer border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 p-0.5">
                        <span class="text-xs text-gray-500 dark:text-slate-400">Pick a color for this tag</span>
                    </div>
                </div>
                <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                    Add Tag
                </button>
            </form>
        </div>

        {{-- Tags List --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Your Tags ({{ count($tags) }})</h3>
            </div>
            @if(count($tags) > 0)
            <div class="divide-y divide-gray-50 dark:divide-slate-700">
                @foreach($tags as $i => $tag)
                <div class="flex items-center justify-between px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $tag['color'] ?? '#3b82f6' }}"></div>
                        <span class="text-sm text-gray-700 dark:text-slate-200">{{ $tag['name'] }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-white"
                              style="background-color: {{ $tag['color'] ?? '#3b82f6' }}">
                            {{ $tag['name'] }}
                        </span>
                    </div>
                    <form method="POST" action="{{ route('settings.tags.destroy') }}" onsubmit="return confirm('Delete this tag?')">
                        @csrf @method('DELETE')
                        <input type="hidden" name="index" value="{{ $i }}">
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
            @else
            <div class="px-4 py-10 text-center text-sm text-gray-400 dark:text-slate-500">
                No tags yet. Create your first tag.
            </div>
            @endif
        </div>
    </div>

</div>

@endsection
