<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: false, darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))">
<head>
    <meta charset="UTF-8">
    {{-- Apply the saved theme before anything paints.

         Alpine sets the `dark` class, but it is loaded with `defer` and Tailwind
         comes from a CDN, so on every navigation the browser painted a white page
         first and only went dark once JS ran — the flash you see when clicking a
         sidebar link. This runs synchronously, ahead of any stylesheet, and the
         inline rule below colours <html> itself so there is nothing white to
         flash even before Tailwind arrives. --}}
    <script>try{if(localStorage.getItem('darkMode')==='true'){document.documentElement.classList.add('dark');}}catch(e){}</script>
    <style>
        html { background: #f9fafb; }
        html.dark { background: #0b1220; color-scheme: dark; }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Eltiz ERP') }} — @yield('title', 'Dashboard')</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#111827">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Eltiz BM">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/icons/icon-152.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/icons/icon-144.png">
    <link rel="apple-touch-icon" sizes="128x128" href="/icons/icon-128.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50:  '{{ $themePalette["50"]  ?? "#eff6ff" }}',
                            100: '{{ $themePalette["100"] ?? "#dbeafe" }}',
                            200: '{{ $themePalette["200"] ?? "#bfdbfe" }}',
                            300: '{{ $themePalette["300"] ?? "#93c5fd" }}',
                            400: '{{ $themePalette["400"] ?? "#60a5fa" }}',
                            500: '{{ $themePalette["500"] ?? "#3b82f6" }}',
                            600: '{{ $themePalette["600"] ?? "#2563eb" }}',
                            700: '{{ $themePalette["700"] ?? "#1d4ed8" }}',
                            800: '{{ $themePalette["800"] ?? "#1e40af" }}',
                            900: '{{ $themePalette["900"] ?? "#1e3a8a" }}',
                        }
                    },
                    screens: { 'xs': '375px' }
                }
            }
        }
    </script>
    @php
        $sidebarBg  = $themeSettings['sidebar_color'] ?? null;
        $headerBg   = $themeSettings['header_color']  ?? null;
        $darkSidebar= $themeSettings['dark_sidebar']  ?? false;
        $p600       = $themePalette['600'] ?? '#2563eb';
        $p700       = $themePalette['700'] ?? '#1d4ed8';
        $p400       = $themePalette['400'] ?? '#60a5fa';
        $p50        = $themePalette['50']  ?? '#eff6ff';
        $p100       = $themePalette['100'] ?? '#dbeafe';
    @endphp
    @if($sidebarBg || $headerBg || ($themeSettings['primary_color'] ?? null) !== '#2563eb')
    <style>
        @if($sidebarBg)
        /* Scoped to :not(.dark) so the custom brand colour applies in light mode
           only — these rules use !important, so without this the sidebar kept its
           light colour after switching to dark mode. */
        html:not(.dark) #sidebar { background: {{ $sidebarBg }} !important; border-color: {{ $sidebarBg }} !important; }
        @if($darkSidebar)
        html:not(.dark) #sidebar .nav-item, html:not(.dark) #sidebar a, html:not(.dark) #sidebar button, html:not(.dark) #sidebar p, html:not(.dark) #sidebar span { color: #374151 !important; }
        html:not(.dark) #sidebar .nav-active { background: rgba(0,0,0,0.08) !important; color: #111 !important; }
        @else
        html:not(.dark) #sidebar .nav-item, html:not(.dark) #sidebar a, html:not(.dark) #sidebar p { color: rgba(255,255,255,0.85) !important; }
        html:not(.dark) #sidebar .nav-active { background: rgba(255,255,255,0.15) !important; color: #fff !important; }
        html:not(.dark) #sidebar .nav-active svg { color: #fff !important; }
        html:not(.dark) #sidebar .text-gray-400, html:not(.dark) #sidebar .text-slate-500 { color: rgba(255,255,255,0.45) !important; }
        @endif
        @endif

        @if($headerBg)
        html:not(.dark) header.sticky { background: {{ $headerBg }} !important; border-color: {{ $headerBg }} !important; }
        @if($headerBg !== '#ffffff')
        html:not(.dark) header.sticky h1, html:not(.dark) header.sticky button, html:not(.dark) header.sticky span { color: rgba(255,255,255,0.9) !important; }
        @endif
        @endif
    </style>
    @endif

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .sidebar-transition { transition: transform 0.3s ease, width 0.3s ease; }
        .nav-active { background: {{ $p50 }}; color: {{ $p600 }}; font-weight: 500; }
        .nav-active svg { color: {{ $p600 }}; }
        .bottom-nav-active { color: {{ $p600 }}; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .stat-card { transition: all 0.2s ease; }
        /* ── Dark theme ────────────────────────────────────────────────
           Deeper, cooler surfaces with a clear step between page, sidebar
           and cards, so panels read as raised instead of blending into one
           flat grey. The active nav item gets a brand-tinted fill and a left
           accent bar so the current page is obvious at a glance.          */
        .dark body { background: #0b1220; color: #e2e8f0; }
        .dark .sidebar { background: #111b2e; border-color: rgba(148,163,184,.14); }
        .dark .sidebar .border-gray-100,
        .dark .sidebar .dark\:border-slate-700 { border-color: rgba(148,163,184,.12) !important; }

        .dark .nav-active {
            background: linear-gradient(90deg, {{ $p600 }}2e 0%, {{ $p600 }}10 100%);
            color: {{ $p400 }};
            box-shadow: inset 3px 0 0 0 {{ $p400 }};
        }
        .dark .nav-active svg { color: {{ $p400 }}; }
        .dark #sidebar a:not(.nav-active):hover { background: rgba(148,163,184,.08); }

        /* Cards / panels: lift them off the page and soften the borders. */
        .dark .bg-white           { background-color: #16223a !important; }
        .dark .dark\:bg-slate-800 { background-color: #16223a !important; }
        .dark .dark\:bg-slate-900 { background-color: #0b1220 !important; }
        .dark .dark\:border-slate-700 { border-color: rgba(148,163,184,.14) !important; }
        .dark .dark\:border-slate-600 { border-color: rgba(148,163,184,.20) !important; }

        /* Inputs sit slightly darker than their card so fields are findable. */
        .dark input, .dark select, .dark textarea { background-color: #0f1a2e !important; }
        .dark input::placeholder, .dark textarea::placeholder { color: #64748b; }

        .dark header.sticky { background: #111b2e !important; border-color: rgba(148,163,184,.14) !important; }
        .dark ::-webkit-scrollbar-track { background: #0b1220; }
        .dark ::-webkit-scrollbar-thumb { background: #334155; }
    </style>

    @stack('styles')
</head>

<body class="bg-gray-50 dark:bg-slate-900 min-h-screen">

<div x-show="sidebarOpen" x-cloak
     x-transition:enter="transition-opacity ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden">
</div>

<aside id="sidebar"
       class="sidebar fixed top-0 left-0 h-full w-64 bg-white dark:bg-slate-800 border-r border-gray-100 dark:border-slate-700 z-50 sidebar-transition flex flex-col"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

    {{-- Logo --}}
    @php
        // Detect if sidebar is dark → invert logo to white
        // Default sidebar is dark (gray-900 = #111827), so default = invert
        $sidebarHex = $themeSettings['sidebar_color'] ?? null;
        $logoInvert = true; // dark by default
        if ($sidebarHex) {
            $hex = ltrim($sidebarHex, '#');
            if (strlen($hex) === 6) {
                [$r, $g, $b] = array_map('hexdec', str_split($hex, 2));
                $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
                $logoInvert = $luminance < 0.5; // dark color → invert logo
            }
        }
        $logoFilter = $logoInvert ? 'filter: brightness(0) invert(1);' : '';
        $logoSrc    = ($business->logo ?? null)
                        ? Storage::url($business->logo)
                        : '/images/logo.png';
        $logoAlt    = $business->name ?? 'Eltiz ERP';
    @endphp
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-slate-700 flex-shrink-0" style="min-height: 60px;">
        <a href="{{ route('dashboard') }}" class="flex items-center min-w-0 flex-1">
            <img src="{{ $logoSrc }}" alt="{{ $logoAlt }}"
                 class="h-10 w-auto object-contain object-left"
                 style="max-width: 148px; {{ $logoFilter }}">
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 flex-shrink-0 ml-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3">

        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2">Main</p>

        <a href="{{ route('dashboard') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ request()->routeIs('dashboard') ? 'nav-active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>

        @if(auth()->user()->hasPermission('orders') || auth()->user()->hasPermission('pos'))
        {{-- Sales group --}}
        @php $salesActive = request()->routeIs('orders.*') || request()->routeIs('web-orders.*') || request()->routeIs('pos.*') || request()->routeIs('wholesale.*') || request()->routeIs('returns.*') || request()->routeIs('steadfast.*'); @endphp
        <div x-data="{ open: {{ $salesActive ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ $salesActive ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Sales
                <svg class="w-3 h-3 ml-auto transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak class="pl-9 space-y-0.5">
                <a href="{{ route('orders.index') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('orders.*') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">Orders</a>
                <a href="{{ route('web-orders.index') }}" class="flex items-center px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('web-orders.*') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">
                    Web Orders
                    @if(isset($pendingWooOrders) && $pendingWooOrders > 0)
                        <span class="ml-auto bg-primary-100 text-primary-600 text-xs font-medium px-2 py-0.5 rounded-full">{{ $pendingWooOrders }}</span>
                    @endif
                </a>
                <a href="{{ route('pos.index') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('pos.*') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">POS</a>
                <a href="{{ route('wholesale.index') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('wholesale.*') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">Wholesale</a>
                <a href="{{ route('returns.index') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('returns.*') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">Returns &amp; RMA</a>
                <a href="{{ route('steadfast.index') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('steadfast.*') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">Steadfast Courier</a>
            </div>
        </div>

        @endif

        @if(auth()->user()->hasPermission('products'))
        <div x-data="{ open: {{ request()->routeIs('products.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ request()->routeIs('products.*') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Products
                <svg class="w-3 h-3 ml-auto transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak class="pl-9 space-y-0.5">
                <a href="{{ route('products.index') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">All Products</a>
                <a href="{{ route('categories.index') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Categories</a>
                <a href="{{ route('brands.index') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Brands</a>
                <a href="{{ route('attributes.index') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Attributes</a>
                <a href="{{ route('products.sync') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Product Sync</a>
            </div>
        </div>

        @endif

        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2 mt-4">Operations</p>

        @if(auth()->user()->hasPermission('inventory'))
        <div x-data="{ open: {{ request()->routeIs('inventory.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ request()->routeIs('inventory.*') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                Inventory
                <svg class="w-3 h-3 ml-auto transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak class="pl-9 space-y-0.5">
                <a href="{{ route('inventory.overview') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Overview</a>
                <a href="{{ route('inventory.purchase-orders') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Purchase Orders</a>
                <a href="{{ route('inventory.stock-levels') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Stock Levels</a>
                <a href="{{ route('inventory.cutting-list') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('inventory.cutting-list') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">✂️ Cutting List</a>
                <a href="{{ route('inventory.adjustments') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Adjustments</a>
                <a href="{{ route('inventory.barcode') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Barcode Print</a>
            </div>
        </div>

        @endif

        @if(auth()->user()->hasPermission('accounts'))
        <div x-data="{ open: {{ request()->routeIs('accounts.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Accounts
                <svg class="w-3 h-3 ml-auto transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak class="pl-9 space-y-0.5">
                <a href="{{ route('accounts.income') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Income</a>
                <a href="{{ route('accounts.expenses') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Expenses</a>
                <a href="{{ route('production.payroll') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Salary (Payroll)</a>
                <a href="{{ route('accounts.payment-transfer') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Payment Transfer</a>
            </div>
        </div>
        @endif

        @if(auth()->user()->hasPermission('reports'))
        <div x-data="{ open: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Reports
                <svg class="w-3 h-3 ml-auto transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak class="pl-9 space-y-0.5">
                <a href="{{ route('reports.profit-loss') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Profit & Loss</a>
                <a href="{{ route('reports.order-analytics') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Order Analytics</a>
                <a href="{{ route('reports.sales-channel') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Sales by Channel</a>
                <a href="{{ route('reports.employee-production') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Employee Production</a>
                <a href="{{ route('reports.expense-category') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Expense by Category</a>
                <a href="{{ route('reports.income-source') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Income Sources</a>
                <a href="{{ route('reports.customer-due') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Customer Due</a>
                <a href="{{ route('reports.supplier-due') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Supplier Due</a>
                <a href="{{ route('reports.business-overview') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Business Overview</a>
            </div>
        </div>
        @endif

        @if(auth()->user()->hasPermission('orders'))
        {{-- Tailoring --}}
        <a href="{{ route('tailoring.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ request()->routeIs('tailoring.*') ? 'nav-active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
            </svg>
            Tailoring
        </a>

        @endif

        @if(auth()->user()->hasPermission('employees'))
        <div x-data="{ open: {{ request()->routeIs('production.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ request()->routeIs('production.*') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                Production
                <svg class="w-3 h-3 ml-auto transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak class="pl-9 space-y-0.5">
                <a href="{{ route('production.admin-overview') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('production.admin-overview') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">📊 Team Overview</a>
                <a href="{{ route('production.rates') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Rates Setup</a>
                <a href="{{ route('production.payroll') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Payroll</a>
                <a href="{{ route('production.pipeline') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('production.pipeline') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">🏭 Pipeline Board</a>
                <a href="{{ route('production.tasks.index') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('production.tasks.*') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">Task Assignment</a>
                <a href="{{ route('production.worker.ledger') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('production.worker.ledger*') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">Worker Ledger</a>
                <a href="{{ route('production.efficiency') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('production.efficiency') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">Efficiency Report</a>
            </div>
        </div>

        @endif

        @if(auth()->user()->hasPermission('inventory'))
        <a href="{{ route('materials.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ request()->routeIs('materials.*') ? 'nav-active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            Materials
        </a>
        @endif

        @if(auth()->user()->hasPermission('employees') || auth()->user()->hasPermission('people'))
        {{-- People & Staff group --}}
        @php $peopleActive = request()->routeIs('employees.*') || request()->routeIs('people.*') || request()->routeIs('tasks.*'); @endphp
        <div x-data="{ open: {{ $peopleActive ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ $peopleActive ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                People &amp; Staff
                <svg class="w-3 h-3 ml-auto transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak class="pl-9 space-y-0.5">
                <a href="{{ route('employees.index') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('employees.*') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">Employees</a>
                @if(auth()->user()->hasPermission('people'))
                <a href="{{ route('people.customers') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('people.*') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">Customers &amp; Vendors</a>
                @endif
                <a href="{{ route('tasks.index') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('tasks.*') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">Tasks (To-do)</a>
            </div>
        </div>

        @endif

        @if(auth()->user()->hasPermission('settings') || auth()->user()->hasPermission('integrations'))
        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2 mt-4">System</p>

        {{-- System group --}}
        @php $systemActive = request()->routeIs('settings.*') || request()->routeIs('integrations.*'); @endphp
        <div x-data="{ open: {{ $systemActive ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ $systemActive ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                System
                <svg class="w-3 h-3 ml-auto transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak class="pl-9 space-y-0.5">
                <a href="{{ route('settings.general') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('settings.*') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">Settings</a>
                <a href="{{ route('integrations.index') }}" class="block px-3 py-2 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 {{ request()->routeIs('integrations.*') ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-500 dark:text-slate-400 hover:text-primary-600' }}">Integrations</a>
            </div>
        </div>
        @endif

    </nav>

    {{-- User info --}}
    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-primary-100 dark:bg-slate-600 rounded-full flex items-center justify-center">
                <span class="text-xs font-semibold text-primary-600 dark:text-slate-200">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ auth()->user()->name ?? 'User' }}</p>
                <p class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ auth()->user()->email ?? '' }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Logout">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- MAIN CONTENT --}}
<div class="lg:ml-64 min-h-screen flex flex-col">

    <header class="sticky top-0 z-30 bg-white dark:bg-slate-800 border-b border-gray-100 dark:border-slate-700 px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700 -ml-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                @php
                    // Mobile header logo: detect if header bg is dark → invert
                    $headerHex = $themeSettings['header_color'] ?? null;
                    $headerLogoInvert = false; // default header is white
                    if ($headerHex) {
                        $hx = ltrim($headerHex, '#');
                        if (strlen($hx) === 6) {
                            [$hr, $hg, $hb] = array_map('hexdec', str_split($hx, 2));
                            $headerLuminance = (0.299 * $hr + 0.587 * $hg + 0.114 * $hb) / 255;
                            $headerLogoInvert = $headerLuminance < 0.5;
                        }
                    }
                    $headerLogoFilter = $headerLogoInvert ? 'filter: brightness(0) invert(1);' : '';
                    $mobileLogoSrc = ($business->logo ?? null) ? Storage::url($business->logo) : '/images/logo.png';
                @endphp
                <div class="flex items-center lg:hidden">
                    {{-- dark:brightness-0 dark:invert handles dark mode automatically --}}
                    <img src="{{ $mobileLogoSrc }}" alt="{{ $business->name ?? 'Eltiz ERP' }}"
                         class="h-9 w-auto object-contain object-left dark:brightness-0 dark:invert"
                         style="max-width:120px; {{ $headerLogoFilter }}">
                </div>
                <div class="hidden lg:block">
                    <h1 class="text-base font-semibold text-gray-900 dark:text-white">@yield('title', 'Dashboard')</h1>
                </div>
            </div>

            <div class="flex items-center gap-2">

                {{-- ⚡ Quick Action Button (desktop only — mobile uses bottom FAB) --}}
                @if(auth()->user()->role !== 'employee')
                <div class="relative hidden lg:block" x-data="{ qaOpen: false }" @keydown.escape.window="qaOpen = false">

                    <button @click="qaOpen = !qaOpen" @click.outside="qaOpen = false"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors"
                            title="Quick Actions">
                        <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="qaOpen ? 'rotate-45' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span class="hidden sm:inline">New</span>
                    </button>

                    {{-- Desktop Dropdown --}}
                    <div x-show="qaOpen" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                         class="absolute right-0 mt-2 w-64 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 z-50 overflow-hidden">

                        <div class="px-4 py-2.5 border-b border-gray-50 dark:border-slate-700">
                            <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider">Quick Actions</p>
                        </div>

                        <div class="p-2 space-y-0.5">

                            {{-- New Order --}}
                            <a href="{{ route('orders.create') }}" @click="qaOpen = false"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors group">
                                <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-primary-200 dark:group-hover:bg-primary-900/50 transition-colors">
                                    <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">New Order</p>
                                    <p class="text-xs text-gray-400 dark:text-slate-500">Create a manual order</p>
                                </div>
                            </a>

                            {{-- POS Sale --}}
                            <a href="{{ route('pos.index') }}" @click="qaOpen = false"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors group">
                                <div class="w-8 h-8 bg-violet-100 dark:bg-violet-900/30 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-violet-200 dark:group-hover:bg-violet-900/50 transition-colors">
                                    <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">POS Sale</p>
                                    <p class="text-xs text-gray-400 dark:text-slate-500">Point of sale terminal</p>
                                </div>
                            </a>

                            {{-- Add Expense --}}
                            <a href="{{ route('accounts.expenses') }}" @click="qaOpen = false"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors group">
                                <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-red-200 dark:group-hover:bg-red-900/50 transition-colors">
                                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">Add Expense</p>
                                    <p class="text-xs text-gray-400 dark:text-slate-500">Record a business expense</p>
                                </div>
                            </a>

                            {{-- Add Income --}}
                            <a href="{{ route('accounts.income') }}" @click="qaOpen = false"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors group">
                                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition-colors">
                                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">Add Income</p>
                                    <p class="text-xs text-gray-400 dark:text-slate-500">Record other income</p>
                                </div>
                            </a>

                            {{-- Stock Adjustment --}}
                            <a href="{{ route('inventory.adjustments') }}" @click="qaOpen = false"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors group">
                                <div class="w-8 h-8 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-amber-200 dark:group-hover:bg-amber-900/50 transition-colors">
                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">Stock Adjustment</p>
                                    <p class="text-xs text-gray-400 dark:text-slate-500">Update inventory levels</p>
                                </div>
                            </a>

                            {{-- Assign Task --}}
                            <a href="{{ route('production.tasks.create') }}" @click="qaOpen = false"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors group">
                                <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-primary-200 dark:group-hover:bg-primary-900/50 transition-colors">
                                    <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">Assign Task</p>
                                    <p class="text-xs text-gray-400 dark:text-slate-500">Give work to a worker</p>
                                </div>
                            </a>

                            {{-- Purchase Order --}}
                            <a href="{{ route('inventory.purchase-orders') }}" @click="qaOpen = false"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors group">
                                <div class="w-8 h-8 bg-teal-100 dark:bg-teal-900/30 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-teal-200 dark:group-hover:bg-teal-900/50 transition-colors">
                                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">Purchase Order</p>
                                    <p class="text-xs text-gray-400 dark:text-slate-500">Restock from supplier</p>
                                </div>
                            </a>

                        </div>
                    </div>
                </div>
                @endif
                {{-- /Quick Action --}}

                <button @click="darkMode = !darkMode" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                    <svg x-show="!darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="darkMode" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>

                <button class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>

                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.outside="open = false"
                            class="w-8 h-8 bg-primary-100 dark:bg-slate-600 rounded-full flex items-center justify-center cursor-pointer hover:ring-2 hover:ring-primary-300 transition-all">
                        <span class="text-xs font-semibold text-primary-600 dark:text-slate-200">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}</span>
                    </button>
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-slate-700 z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ auth()->user()->email }}</p>
                            <span class="inline-flex mt-1 px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400">
                                {{ ucfirst(auth()->user()->role ?? 'user') }}
                            </span>
                        </div>
                        <div class="py-1">
                            <a href="{{ route('settings.general') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                My Profile
                            </a>
                            <a href="{{ route('settings.business') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Business Settings
                            </a>
                            <a href="{{ route('settings.user-management') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Team Members
                            </a>
                            <button @click="darkMode = !darkMode; open = false"
                                    class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700">
                                <svg x-show="!darkMode" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                                <svg x-show="darkMode" x-cloak class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                <span x-text="darkMode ? 'Light Mode' : 'Dark Mode'"></span>
                            </button>
                        </div>
                        <div class="border-t border-gray-100 dark:border-slate-700 py-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         class="mx-4 mt-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg flex items-center gap-2">
        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        <span class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</span>
        <button @click="show = false" class="ml-auto text-green-400 hover:text-green-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="mx-4 mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg flex items-center gap-2">
        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
        <span class="text-sm text-red-700 dark:text-red-400">{{ session('error') }}</span>
        <button @click="show = false" class="ml-auto text-red-400 hover:text-red-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    <main class="flex-1 p-4 pb-24 lg:pb-6">
        @yield('content')
    </main>

    <footer class="hidden lg:block text-center py-4 text-xs text-gray-400 dark:text-slate-500 border-t border-gray-100 dark:border-slate-700">
        © {{ date('Y') }} Eltiz ERP. All rights reserved.
    </footer>
</div>

{{-- MOBILE BOTTOM NAV --}}
<div class="lg:hidden" x-data="{ qaSheet: false }" @keydown.escape.window="qaSheet = false">

    {{-- Quick Action Bottom Sheet Backdrop --}}
    <div x-show="qaSheet" x-cloak
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="qaSheet = false"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40">
    </div>

    {{-- Quick Action Bottom Sheet --}}
    @if(auth()->user()->role !== 'employee')
    <div x-show="qaSheet" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-full"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-full"
         class="fixed bottom-16 left-0 right-0 z-50 bg-white dark:bg-slate-800 rounded-t-3xl shadow-2xl border-t border-gray-100 dark:border-slate-700 pb-safe">

        {{-- Handle --}}
        <div class="flex justify-center pt-3 pb-1">
            <div class="w-10 h-1 bg-gray-200 dark:bg-slate-600 rounded-full"></div>
        </div>

        <div class="px-4 py-2 border-b border-gray-50 dark:border-slate-700">
            <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider text-center">Quick Actions</p>
        </div>

        <div class="p-3 grid grid-cols-3 gap-2.5">

            <a href="{{ route('orders.create') }}" @click="qaSheet = false"
               class="flex flex-col items-center gap-2 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-2xl hover:bg-primary-100 dark:hover:bg-primary-900/30 active:scale-95 transition-all">
                <div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <span class="text-xs font-semibold text-primary-700 dark:text-primary-400 text-center leading-tight">New Order</span>
            </a>

            <a href="{{ route('pos.index') }}" @click="qaSheet = false"
               class="flex flex-col items-center gap-2 p-3 bg-violet-50 dark:bg-violet-900/20 rounded-2xl hover:bg-violet-100 dark:hover:bg-violet-900/30 active:scale-95 transition-all">
                <div class="w-10 h-10 bg-violet-600 rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-xs font-semibold text-violet-700 dark:text-violet-400 text-center leading-tight">POS Sale</span>
            </a>

            <a href="{{ route('accounts.expenses') }}" @click="qaSheet = false"
               class="flex flex-col items-center gap-2 p-3 bg-red-50 dark:bg-red-900/20 rounded-2xl hover:bg-red-100 dark:hover:bg-red-900/30 active:scale-95 transition-all">
                <div class="w-10 h-10 bg-red-500 rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-semibold text-red-700 dark:text-red-400 text-center leading-tight">Expense</span>
            </a>

            <a href="{{ route('accounts.income') }}" @click="qaSheet = false"
               class="flex flex-col items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 rounded-2xl hover:bg-green-100 dark:hover:bg-green-900/30 active:scale-95 transition-all">
                <div class="w-10 h-10 bg-green-600 rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-semibold text-green-700 dark:text-green-400 text-center leading-tight">Add Income</span>
            </a>

            <a href="{{ route('inventory.adjustments') }}" @click="qaSheet = false"
               class="flex flex-col items-center gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-2xl hover:bg-amber-100 dark:hover:bg-amber-900/30 active:scale-95 transition-all">
                <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </div>
                <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 text-center leading-tight">Stock Adj.</span>
            </a>

            <a href="{{ route('production.tasks.create') }}" @click="qaSheet = false"
               class="flex flex-col items-center gap-2 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-2xl hover:bg-primary-100 dark:hover:bg-primary-900/30 active:scale-95 transition-all">
                <div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <span class="text-xs font-semibold text-primary-700 dark:text-primary-400 text-center leading-tight">Assign Task</span>
            </a>

            <a href="{{ route('inventory.purchase-orders') }}" @click="qaSheet = false"
               class="flex flex-col items-center gap-2 p-3 bg-teal-50 dark:bg-teal-900/20 rounded-2xl hover:bg-teal-100 dark:hover:bg-teal-900/30 active:scale-95 transition-all">
                <div class="w-10 h-10 bg-teal-600 rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
                <span class="text-xs font-semibold text-teal-700 dark:text-teal-400 text-center leading-tight">Purchase</span>
            </a>

            <a href="{{ route('reports.profit-loss') }}" @click="qaSheet = false"
               class="flex flex-col items-center gap-2 p-3 bg-gray-50 dark:bg-slate-700/50 rounded-2xl hover:bg-gray-100 dark:hover:bg-slate-700 active:scale-95 transition-all">
                <div class="w-10 h-10 bg-gray-600 dark:bg-slate-500 rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <span class="text-xs font-semibold text-gray-700 dark:text-slate-300 text-center leading-tight">P&L Report</span>
            </a>

            <a href="{{ route('returns.index') }}" @click="qaSheet = false"
               class="flex flex-col items-center gap-2 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-2xl hover:bg-orange-100 dark:hover:bg-orange-900/30 active:scale-95 transition-all">
                <div class="w-10 h-10 bg-orange-500 rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                </div>
                <span class="text-xs font-semibold text-orange-700 dark:text-orange-400 text-center leading-tight">Returns</span>
            </a>

        </div>
        <div class="h-4"></div>
    </div>
    @endif

    <nav class="fixed bottom-0 left-0 right-0 z-30 bg-white dark:bg-slate-800 border-t border-gray-100 dark:border-slate-700">
        <div class="grid grid-cols-5 h-16">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('dashboard') ? 'bottom-nav-active text-primary-600' : 'text-gray-400 dark:text-slate-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Home</span>
            </a>
            <a href="{{ route('orders.index') }}" class="flex flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('orders.*') ? 'bottom-nav-active text-primary-600' : 'text-gray-400 dark:text-slate-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Orders</span>
            </a>

            {{-- Centre: "+" Quick Action FAB (admin) or POS (employee) --}}
            @if(auth()->user()->role !== 'employee')
            <button @click="qaSheet = !qaSheet"
                    class="flex flex-col items-center justify-center gap-1 text-xs text-gray-400 dark:text-slate-500">
                <div class="w-12 h-12 -mt-6 rounded-full flex items-center justify-center shadow-lg transition-all"
                     :class="qaSheet ? 'bg-gray-700 rotate-45' : 'bg-primary-600'"
                     style="transition: transform 0.2s, background-color 0.2s;">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <span class="mt-1" x-text="qaSheet ? 'Close' : 'Actions'"></span>
            </button>
            @else
            <a href="{{ route('production.worker.dashboard') }}" class="flex flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('production.worker.*') ? 'bottom-nav-active text-primary-600' : 'text-gray-400 dark:text-slate-500' }}">
                <div class="w-10 h-10 -mt-5 bg-primary-600 rounded-full flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <span class="mt-1">My Tasks</span>
            </a>
            @endif

            <a href="{{ route('products.index') }}" class="flex flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('products.*') ? 'bottom-nav-active text-primary-600' : 'text-gray-400 dark:text-slate-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Products</span>
            </a>
            <a href="{{ route('steadfast.index') }}" class="flex flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('steadfast.*') ? 'bottom-nav-active text-primary-600' : 'text-gray-400 dark:text-slate-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Courier</span>
            </a>
        </div>
    </nav>
</div>

@stack('scripts')

{{-- Global double-submit guard: disables a form's submit button after it
     submits (post-submit, so the button's value is still sent), preventing
     accidental duplicate payments/tasks/orders. Opt out with data-no-lock. --}}
<script>
document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.tagName !== 'FORM' || form.hasAttribute('data-no-lock')) return;
    var btn = form.querySelector('button[type="submit"], input[type="submit"]');
    if (!btn || btn.disabled) return;
    // Defer so the click's name/value is included in the submission.
    setTimeout(function () {
        btn.disabled = true;
        btn.style.opacity = '0.6';
        btn.style.cursor = 'wait';
    }, 0);
}, true);
</script>

{{-- PWA Service Worker --}}
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .catch(() => {});
    });
}
</script>
</body>
</html>