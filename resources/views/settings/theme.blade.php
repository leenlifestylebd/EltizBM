@extends('layouts.app')
@section('title', 'Theme Settings')

@section('content')
@php
    $theme    = $business->theme_settings ?? [];
    $primary  = $theme['primary_color'] ?? '#2563eb';
    $sidebar  = $theme['sidebar_color'] ?? '';
    $header   = $theme['header_color']  ?? '';
    $darkSide = $theme['dark_sidebar']  ?? false;

    // Preset palettes
    $presets = [
        ['name'=>'Blue (Default)', 'color'=>'#2563eb'],
        ['name'=>'Indigo',         'color'=>'#4f46e5'],
        ['name'=>'Violet',         'color'=>'#7c3aed'],
        ['name'=>'Rose',           'color'=>'#e11d48'],
        ['name'=>'Orange',         'color'=>'#ea580c'],
        ['name'=>'Amber',          'color'=>'#d97706'],
        ['name'=>'Green',          'color'=>'#16a34a'],
        ['name'=>'Teal',           'color'=>'#0d9488'],
        ['name'=>'Cyan',           'color'=>'#0891b2'],
        ['name'=>'Slate',          'color'=>'#475569'],
        ['name'=>'Black',          'color'=>'#111827'],
        ['name'=>'Pink',           'color'=>'#db2777'],
    ];
@endphp

<div class="max-w-3xl mx-auto">

    {{-- Tabs --}}
    <div class="flex gap-1 mb-5 bg-gray-100 dark:bg-slate-700 p-1 rounded-xl overflow-x-auto scrollbar-hide">
        @foreach([
            ['route'=>'settings.general',          'label'=>'Profile'],
            ['route'=>'settings.business',         'label'=>'Business'],
            ['route'=>'settings.user-management',  'label'=>'Users'],
            ['route'=>'settings.warehouses',       'label'=>'Warehouses'],
            ['route'=>'settings.print-templates',  'label'=>'Print'],
            ['route'=>'settings.account-categories','label'=>'Categories'],
            ['route'=>'settings.theme',            'label'=>'Theme'],
        ] as $tab)
        <a href="{{ route($tab['route']) }}"
           class="flex-shrink-0 px-3 py-2 rounded-lg text-xs font-medium transition-colors whitespace-nowrap
                  {{ request()->routeIs($tab['route'])
                      ? 'bg-white dark:bg-slate-800 text-gray-900 dark:text-white shadow-sm'
                      : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300' }}">
            {{ $tab['label'] }}
        </a>
        @endforeach
    </div>

    @if(session('success'))
    <div class="p-3 mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-400">
        {{ session('success') }}
    </div>
    @endif

    <form method="POST" action="{{ route('settings.theme.update') }}" x-data="{
        primary: '{{ $primary }}',
        sidebar: '{{ $sidebar }}',
        header:  '{{ $header }}',
    }">
        @csrf

        {{-- Live Preview --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Live Preview</h3>
            <div class="flex items-center gap-3 flex-wrap">
                {{-- Button preview --}}
                <div class="flex items-center gap-2">
                    <button type="button"
                            :style="`background:${primary}`"
                            class="px-4 py-2 text-white text-sm font-semibold rounded-lg transition-all">
                        Button
                    </button>
                    <button type="button"
                            :style="`background:${primary}22; color:${primary}; border:1px solid ${primary}44`"
                            class="px-4 py-2 text-sm font-semibold rounded-lg transition-all">
                        Outline
                    </button>
                </div>

                {{-- Nav active preview --}}
                <div :style="`background:${primary}18; color:${primary}`"
                     class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Active Nav Item
                </div>

                {{-- Badge --}}
                <span :style="`background:${primary}18; color:${primary}`"
                      class="px-2.5 py-1 text-xs font-semibold rounded-full">Badge</span>

                {{-- Link --}}
                <a href="#" :style="`color:${primary}`" class="text-sm font-medium underline" @click.prevent>Link</a>

                {{-- Color swatch --}}
                <div class="flex gap-1">
                    @foreach(['50','100','200','300','400','500','600','700','800','900'] as $shade)
                    <div class="w-5 h-5 rounded"
                         :style="`background: var(--tw-preview-${$shade}, #2563eb)`"
                         title="{{ $shade }}"></div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Primary Color --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Primary Color</h3>
            <p class="text-xs text-gray-400 dark:text-slate-500 mb-4">Used for buttons, active navigation, links, and focus rings.</p>

            {{-- Presets --}}
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($presets as $p)
                <button type="button"
                        @click="primary = '{{ $p['color'] }}'; $refs.primaryInput.value = '{{ $p['color'] }}'"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-all hover:scale-105 active:scale-95"
                        :class="primary === '{{ $p['color'] }}' ? 'ring-2 ring-offset-1' : 'border-gray-200 dark:border-slate-600'"
                        :style="primary === '{{ $p['color'] }}' ? `border-color:{{ $p['color'] }};ring-color:{{ $p['color'] }}` : ''">
                    <span class="w-3.5 h-3.5 rounded-full flex-shrink-0"
                          style="background: {{ $p['color'] }}"></span>
                    {{ $p['name'] }}
                </button>
                @endforeach
            </div>

            {{-- Custom picker --}}
            <div class="flex items-center gap-3">
                <label class="text-xs font-medium text-gray-600 dark:text-slate-400">Custom:</label>
                <div class="flex items-center gap-2 border border-gray-200 dark:border-slate-600 rounded-lg px-3 py-2">
                    <input type="color" x-ref="primaryInput" name="primary_color" x-model="primary"
                           class="w-8 h-8 rounded cursor-pointer border-0 bg-transparent p-0">
                    <input type="text" x-model="primary"
                           @input="$refs.primaryInput.value = primary"
                           class="w-24 text-sm font-mono bg-transparent text-gray-700 dark:text-slate-300 border-0 focus:outline-none uppercase"
                           placeholder="#2563eb" maxlength="7">
                </div>
            </div>
        </div>

        {{-- Sidebar Color --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Sidebar Color</h3>
            <p class="text-xs text-gray-400 dark:text-slate-500 mb-4">Leave blank for default (white / dark slate).</p>

            <div class="flex items-center gap-3 flex-wrap">
                {{-- Presets --}}
                @foreach([
                    ['label'=>'Default',    'color'=>''],
                    ['label'=>'Dark',       'color'=>'#111827'],
                    ['label'=>'Slate',      'color'=>'#1e293b'],
                    ['label'=>'Navy',       'color'=>'#1e3a5f'],
                    ['label'=>'Forest',     'color'=>'#14532d'],
                    ['label'=>'Maroon',     'color'=>'#4a1942'],
                ] as $p)
                <button type="button"
                        @click="sidebar = '{{ $p['color'] }}'"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-all"
                        :class="sidebar === '{{ $p['color'] }}' ? 'border-gray-800 ring-2 ring-gray-400 ring-offset-1' : 'border-gray-200 dark:border-slate-600'">
                    <span class="w-3.5 h-3.5 rounded-full border border-gray-300 flex-shrink-0"
                          style="background: {{ $p['color'] ?: '#ffffff' }}"></span>
                    {{ $p['label'] }}
                </button>
                @endforeach

                <div class="flex items-center gap-2 border border-gray-200 dark:border-slate-600 rounded-lg px-3 py-2">
                    <input type="color" name="sidebar_color" x-model="sidebar"
                           class="w-8 h-8 rounded cursor-pointer border-0 bg-transparent p-0">
                    <input type="text" x-model="sidebar"
                           class="w-24 text-sm font-mono bg-transparent text-gray-700 dark:text-slate-300 border-0 focus:outline-none uppercase"
                           placeholder="Custom" maxlength="7">
                </div>
            </div>

            <label class="flex items-center gap-2 mt-3 cursor-pointer">
                <input type="checkbox" name="dark_sidebar" value="1" {{ $darkSide ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="text-xs text-gray-600 dark:text-slate-400">Use dark text theme for sidebar (for light sidebar backgrounds)</span>
            </label>
        </div>

        {{-- Header Color --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-5 mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Header Bar Color</h3>
            <p class="text-xs text-gray-400 dark:text-slate-500 mb-4">Top navigation bar background. Leave blank for default.</p>

            <div class="flex items-center gap-3 flex-wrap">
                @foreach([
                    ['label'=>'Default', 'color'=>''],
                    ['label'=>'Primary', 'color'=>'__primary__'],
                    ['label'=>'Dark',    'color'=>'#111827'],
                    ['label'=>'White',   'color'=>'#ffffff'],
                ] as $p)
                <button type="button"
                        @click="header = '{{ $p['color'] === '__primary__' ? '' : $p['color'] }}'; {{ $p['color'] === '__primary__' ? 'header = primary' : '' }}"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-all"
                        :class="header === '{{ $p['color'] }}' ? 'border-gray-800 ring-2 ring-gray-400 ring-offset-1' : 'border-gray-200 dark:border-slate-600'">
                    <span class="w-3.5 h-3.5 rounded-full border border-gray-300 flex-shrink-0"
                          :style="'background: {{ $p['color'] === '__primary__' ? '' : ($p['color'] ?: '#ffffff') }}' + '{{ $p['color'] === '__primary__' ? '' : '' }}'"></span>
                    {{ $p['label'] }}
                </button>
                @endforeach

                <div class="flex items-center gap-2 border border-gray-200 dark:border-slate-600 rounded-lg px-3 py-2">
                    <input type="color" name="header_color" x-model="header"
                           class="w-8 h-8 rounded cursor-pointer border-0 bg-transparent p-0">
                    <input type="text" x-model="header"
                           class="w-24 text-sm font-mono bg-transparent text-gray-700 dark:text-slate-300 border-0 focus:outline-none uppercase"
                           placeholder="Custom" maxlength="7">
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-xl text-sm transition-colors">
                Save Theme
            </button>
            <form method="POST" action="{{ route('settings.theme.reset') }}">
                @csrf
                <button type="submit"
                        class="px-5 py-3 text-sm font-medium bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors"
                        onclick="return confirm('Reset to default theme?')">
                    Reset Default
                </button>
            </form>
        </div>
    </form>
</div>
@endsection
