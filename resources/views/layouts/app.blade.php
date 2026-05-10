<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: false, darkMode: false }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Fulfillo') }} — @yield('title', 'Dashboard')</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50:  '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    },
                    screens: {
                        'xs': '375px',
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        /* Sidebar transition */
        .sidebar-transition { transition: transform 0.3s ease, width 0.3s ease; }

        /* Active nav item */
        .nav-active { background: #eff6ff; color: #2563eb; font-weight: 500; }
        .nav-active svg { color: #2563eb; }

        /* Mobile bottom nav active */
        .bottom-nav-active { color: #2563eb; }

        /* Card hover */
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .stat-card { transition: all 0.2s ease; }

        /* Dark mode sidebar */
        .dark .sidebar { background: #1e293b; border-color: #334155; }
        .dark .nav-active { background: #1e3a5f; color: #60a5fa; }
        .dark body { background: #0f172a; color: #e2e8f0; }
    </style>

    @stack('styles')
</head>

<body class="bg-gray-50 dark:bg-slate-900 min-h-screen">

{{-- ===================== MOBILE SIDEBAR OVERLAY ===================== --}}
<div x-show="sidebarOpen"
     x-cloak
     x-transition:enter="transition-opacity ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden">
</div>

{{-- ===================== SIDEBAR ===================== --}}
<aside id="sidebar"
       class="sidebar fixed top-0 left-0 h-full w-64 bg-white dark:bg-slate-800 border-r border-gray-100 dark:border-slate-700 z-50 sidebar-transition flex flex-col"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

    {{-- Logo --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <span class="font-semibold text-gray-900 dark:text-white text-lg">Fulfillo</span>
        </div>
        <button @click="sidebarOpen = false" class="lg:hidden p-1 rounded text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3">

        {{-- MAIN --}}
        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2">Main</p>

        <a href="{{ route('dashboard') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ request()->routeIs('dashboard') ? 'nav-active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        <a href="{{ route('orders.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ request()->routeIs('orders.*') ? 'nav-active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Orders
        </a>

        <a href="{{ route('web-orders.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ request()->routeIs('web-orders.*') ? 'nav-active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
            </svg>
            Web Orders
            @if(isset($pendingWooOrders) && $pendingWooOrders > 0)
                <span class="ml-auto bg-blue-100 text-blue-600 text-xs font-medium px-2 py-0.5 rounded-full">{{ $pendingWooOrders }}</span>
            @endif
        </a>

        {{-- Products dropdown --}}
        <div x-data="{ open: {{ request()->routeIs('products.*') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ request()->routeIs('products.*') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Products
                <svg class="w-3 h-3 ml-auto transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="pl-9 space-y-0.5">
                <a href="{{ route('products.index') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">All Products</a>
                <a href="{{ route('categories.index') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Categories</a>
                <a href="{{ route('brands.index') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Brands</a>
                <a href="{{ route('attributes.index') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Attributes</a>
                <a href="{{ route('products.sync') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Product Sync</a>
            </div>
        </div>

        

        {{-- OPERATIONS --}}
        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2 mt-4">Operations</p>

        {{-- Inventory --}}
        <div x-data="{ open: {{ request()->routeIs('inventory.*') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ request()->routeIs('inventory.*') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                Inventory
                <svg class="w-3 h-3 ml-auto transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="pl-9 space-y-0.5">
                <a href="{{ route('inventory.overview') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Overview</a>
                <a href="{{ route('inventory.purchase-orders') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Purchase Orders</a>
                <a href="{{ route('inventory.stock-levels') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Stock Levels</a>
                <a href="{{ route('inventory.adjustments') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Adjustments</a>
                <a href="{{ route('inventory.barcode') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Barcode Print</a>
                <a href="{{ route('inventory.transfers') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Stock Transfers</a>
            </div>
        </div>

        {{-- Accounts --}}
        <div x-data="{ open: {{ request()->routeIs('accounts.*') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Accounts
                <svg class="w-3 h-3 ml-auto transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="pl-9 space-y-0.5">
                <a href="{{ route('accounts.income') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Income</a>
                <a href="{{ route('accounts.expenses') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Expenses</a>
                <a href="{{ route('accounts.salary') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Salary</a>
                <a href="{{ route('accounts.payment-transfer') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Payment Transfer</a>
            </div>
        </div>

        {{-- Reports --}}
        <div x-data="{ open: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Reports
                <svg class="w-3 h-3 ml-auto transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="pl-9 space-y-0.5">
                <a href="{{ route('reports.profit-loss') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Profit & Loss</a>
                <a href="{{ route('reports.order-analytics') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Order Analytics</a>
                <a href="{{ route('reports.business-overview') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Business Overview</a>
            </div>
        </div>

        <a href="{{ route('people.customers') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            People
        </a>

        {{-- Production --}}
        <div x-data="{ open: {{ request()->routeIs('production.*') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ request()->routeIs('production.*') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                Production
                <svg class="w-3 h-3 ml-auto transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="pl-9 space-y-0.5">
                <a href="{{ route('production.index') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Daily Entry</a>
                <a href="{{ route('production.rates') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Rates Setup</a>
                <a href="{{ route('production.payroll') }}" class="block px-3 py-2 text-xs text-gray-500 dark:text-slate-400 hover:text-primary-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700">Payroll</a>
            </div>
        </div>

        <a href="{{ route('employees.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Employees
        </a>

        <a href="{{ route('pos.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            POS
        </a>

        <a href="{{ route('tasks.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Tasks
        </a>

        {{-- SYSTEM --}}
        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2 mt-4">System</p>

        <a href="{{ route('settings.general') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Settings
        </a>
        <a href="{{ route('steadfast.index') }}"
        class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5 {{ request()->routeIs('steadfast.*') ? 'nav-active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            Steadfast
        </a>
        <a href="{{ route('integrations.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 mb-0.5">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            Integrations
        </a>

    </nav>

    {{-- User info at bottom --}}
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
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- ===================== MAIN CONTENT ===================== --}}
<div class="lg:ml-64 min-h-screen flex flex-col">

    {{-- Top Header --}}
    <header class="sticky top-0 z-30 bg-white dark:bg-slate-800 border-b border-gray-100 dark:border-slate-700 px-4 py-3">
        <div class="flex items-center justify-between">
            {{-- Mobile: Hamburger + Logo --}}
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700 -ml-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="flex items-center gap-2 lg:hidden">
                    <div class="w-7 h-7 bg-primary-600 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">Fulfillo</span>
                </div>

                {{-- Desktop: Page title --}}
                <div class="hidden lg:block">
                    <h1 class="text-base font-semibold text-gray-900 dark:text-white">@yield('title', 'Dashboard')</h1>
                </div>
            </div>

            {{-- Right actions --}}
            <div class="flex items-center gap-2">
                {{-- Dark mode toggle --}}
                <button @click="darkMode = !darkMode" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                    <svg x-show="!darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg x-show="darkMode" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>

                {{-- Notifications --}}
                <button class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>

                {{-- Avatar Dropdown --}}
<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" @click.outside="open = false"
            class="w-8 h-8 bg-primary-100 dark:bg-slate-600 rounded-full flex items-center justify-center cursor-pointer hover:ring-2 hover:ring-primary-300 transition-all">
        <span class="text-xs font-semibold text-primary-600 dark:text-slate-200">
            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
        </span>
    </button>

    {{-- Dropdown Menu --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-slate-700 z-50 overflow-hidden">

        {{-- User Info --}}
        <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ auth()->user()->name }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ auth()->user()->email }}</p>
            <span class="inline-flex mt-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                {{ ucfirst(auth()->user()->role ?? 'user') }}
            </span>
        </div>

        {{-- Menu Items --}}
        <div class="py-1">
            <a href="{{ route('settings.general') }}"
               class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                My Profile
            </a>
            <a href="{{ route('settings.business') }}"
               class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Business Settings
            </a>
            <a href="{{ route('settings.user-management') }}"
               class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Team Members
            </a>

            {{-- Dark mode toggle --}}
            <button @click="darkMode = !darkMode; open = false"
                    class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                <svg x-show="!darkMode" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg x-show="darkMode" x-cloak class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span x-text="darkMode ? 'Light Mode' : 'Dark Mode'"></span>
            </button>
        </div>

        {{-- Logout --}}
        <div class="border-t border-gray-100 dark:border-slate-700 py-1">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
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

    {{-- Flash Messages --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         class="mx-4 mt-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg flex items-center gap-2">
        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</span>
        <button @click="show = false" class="ml-auto text-green-400 hover:text-green-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="mx-4 mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg flex items-center gap-2">
        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <span class="text-sm text-red-700 dark:text-red-400">{{ session('error') }}</span>
        <button @click="show = false" class="ml-auto text-red-400 hover:text-red-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    {{-- Page Content --}}
    <main class="flex-1 p-4 pb-24 lg:pb-6">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="hidden lg:block text-center py-4 text-xs text-gray-400 dark:text-slate-500 border-t border-gray-100 dark:border-slate-700">
        © {{ date('Y') }} Fulfillo. All rights reserved.
    </footer>
</div>

{{-- ===================== MOBILE BOTTOM NAV ===================== --}}
<nav class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-white dark:bg-slate-800 border-t border-gray-100 dark:border-slate-700">
    <div class="grid grid-cols-5 h-16">
        <a href="{{ route('dashboard') }}"
           class="flex flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('dashboard') ? 'bottom-nav-active text-primary-600' : 'text-gray-400 dark:text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Home</span>
        </a>
        <a href="{{ route('orders.index') }}"
           class="flex flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('orders.*') ? 'bottom-nav-active text-primary-600' : 'text-gray-400 dark:text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span>Orders</span>
        </a>
        <a href="{{ route('pos.index') }}"
           class="flex flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('pos.*') ? 'bottom-nav-active text-primary-600' : 'text-gray-400 dark:text-slate-500' }}">
            <div class="w-10 h-10 -mt-5 bg-primary-600 rounded-full flex items-center justify-center shadow-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="mt-1">POS</span>
        </a>
        <a href="{{ route('products.index') }}"
           class="flex flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('products.*') ? 'bottom-nav-active text-primary-600' : 'text-gray-400 dark:text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span>Products</span>
        </a>
        <a href="{{ route('courier.index') }}"
           class="flex flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('courier.*') ? 'bottom-nav-active text-primary-600' : 'text-gray-400 dark:text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            <span>Courier</span>
        </a>
    </div>
</nav>

@stack('scripts')
</body>
</html>
