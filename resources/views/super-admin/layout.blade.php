<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Super Admin — @yield('title', 'Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen">

{{-- Top Nav --}}
<nav class="bg-gray-900 border-b border-gray-800 px-6 py-3 flex items-center justify-between sticky top-0 z-50">
    <div class="flex items-center gap-6">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 bg-red-600 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <span class="text-white font-bold text-sm">Super Admin</span>
            <span class="bg-red-600 text-white text-xs px-1.5 py-0.5 rounded font-semibold">PRIVATE</span>
        </div>
        <div class="hidden sm:flex items-center gap-1">
            <a href="{{ route('super-admin.index') }}"
               class="px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('super-admin.index') ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }} transition-colors">
                Dashboard
            </a>
            <a href="{{ route('super-admin.businesses') }}"
               class="px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('super-admin.businesses*') ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }} transition-colors">
                Businesses
            </a>
            <a href="{{ route('super-admin.users') }}"
               class="px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('super-admin.users*') ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }} transition-colors">
                Users
            </a>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard') }}"
           class="text-xs text-gray-400 hover:text-white transition-colors flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to App
        </a>
        <span class="text-xs text-gray-500">{{ auth()->user()->email }}</span>
    </div>
</nav>

{{-- Flash Messages --}}
@if(session('success'))
<div class="mx-6 mt-4 px-4 py-3 bg-green-900/50 border border-green-700 rounded-xl text-green-300 text-sm flex items-center gap-2"
     x-data x-init="setTimeout(() => $el.remove(), 4000)">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
    </svg>
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mx-6 mt-4 px-4 py-3 bg-red-900/50 border border-red-700 rounded-xl text-red-300 text-sm"
     x-data x-init="setTimeout(() => $el.remove(), 4000)">
    {{ session('error') }}
</div>
@endif

{{-- Main Content --}}
<main class="px-4 sm:px-6 py-6 max-w-7xl mx-auto">
    @yield('content')
</main>

</body>
</html>
