<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ELTIZ — @yield('title')</title>
    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#111827">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Eltiz BM">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .dot-bg {
            background-image: radial-gradient(rgba(255,255,255,0.07) 1px, transparent 1px);
            background-size: 22px 22px;
        }
    </style>
</head>
<body class="min-h-screen bg-white flex overflow-hidden">

    {{-- ══ LEFT: Brand Panel ══ --}}
    <div class="hidden lg:flex w-[38%] bg-[#09090b] flex-col justify-between p-10 dot-bg relative overflow-hidden flex-shrink-0">
        {{-- Ambient glows --}}
        <div class="absolute -top-20 -left-20 w-72 h-72 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-56 h-56 bg-violet-600/10 rounded-full blur-3xl pointer-events-none"></div>

        {{-- Brand mark --}}
        <div class="relative z-10 flex items-center gap-3">
            <div class="w-9 h-9 bg-white rounded-xl flex items-center justify-center shadow-lg">
                <svg class="w-5 h-5 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 4a1 1 0 011-1h14a1 1 0 010 2H3a1 1 0 01-1-1zM2 9a1 1 0 011-1h9a1 1 0 010 2H3a1 1 0 01-1-1zM2 14a1 1 0 011-1h14a1 1 0 010 2H3a1 1 0 01-1-1z"/>
                </svg>
            </div>
            <span class="text-white text-xl font-black tracking-tight">ELTIZ</span>
        </div>

        {{-- Hero text --}}
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-3 py-1 mb-6">
                <div class="w-1.5 h-1.5 rounded-full bg-green-400"></div>
                <span class="text-xs text-gray-400">14-day free trial</span>
            </div>
            <h2 class="text-3xl font-black text-white leading-tight mb-3">
                Start growing<br>
                your business<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-violet-400">today.</span>
            </h2>
            <p class="text-gray-500 text-sm leading-relaxed mb-8">
                Join businesses already managing orders, inventory &amp; couriers with ELTIZ.
            </p>

            {{-- Social proof chip --}}
            <div class="flex items-center gap-3 bg-white/5 border border-white/10 rounded-2xl p-4">
                <div class="flex -space-x-2">
                    @foreach(['bg-blue-400','bg-violet-400','bg-green-400'] as $c)
                    <div class="w-7 h-7 rounded-full {{ $c }} border-2 border-[#09090b]"></div>
                    @endforeach
                </div>
                <div>
                    <p class="text-white text-xs font-semibold">Trusted by businesses</p>
                    <p class="text-gray-500 text-xs">across Bangladesh</p>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="relative z-10">
            <p class="text-gray-700 text-xs">© {{ date('Y') }} ELTIZ. All rights reserved.</p>
        </div>
    </div>

    {{-- ══ RIGHT: Form Panel ══ --}}
    <div class="flex-1 flex flex-col items-center justify-center min-h-screen p-6 sm:p-10 overflow-y-auto bg-white">

        {{-- Mobile brand --}}
        <div class="lg:hidden text-center mb-6">
            <div class="inline-flex items-center gap-2 mb-1">
                <div class="w-7 h-7 bg-gray-900 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 4a1 1 0 011-1h14a1 1 0 010 2H3a1 1 0 01-1-1zM2 9a1 1 0 011-1h9a1 1 0 010 2H3a1 1 0 01-1-1zM2 14a1 1 0 011-1h14a1 1 0 010 2H3a1 1 0 01-1-1z"/>
                    </svg>
                </div>
                <span class="text-xl font-black text-gray-900 tracking-tight">ELTIZ</span>
            </div>
            <p class="text-xs text-gray-400">Business Management Platform</p>
        </div>

        <div class="w-full max-w-sm py-4">
            @yield('content')
        </div>
    </div>

</body>
</html>
