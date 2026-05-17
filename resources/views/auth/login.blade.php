<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ELTIZ — Sign In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .dot-bg {
            background-image: radial-gradient(rgba(255,255,255,0.07) 1px, transparent 1px);
            background-size: 22px 22px;
        }
        input[type=checkbox]:checked { background-color: #111; border-color: #111; }
    </style>
</head>
<body class="min-h-screen bg-white flex overflow-hidden">

    {{-- ══ LEFT: Brand Panel ══ --}}
    <div class="hidden lg:flex w-[42%] bg-[#09090b] flex-col justify-between p-10 dot-bg relative overflow-hidden flex-shrink-0">
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

        {{-- Center hero --}}
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-3 py-1 mb-6">
                <div class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></div>
                <span class="text-xs text-gray-400">Business Management Platform</span>
            </div>
            <h2 class="text-4xl font-black text-white leading-tight mb-3">
                Run your<br>business<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-violet-400">smarter.</span>
            </h2>
            <p class="text-gray-500 text-sm leading-relaxed mb-10">
                Orders, inventory, employees &amp; courier — everything in one clean dashboard.
            </p>

        </div>

        {{-- Footer --}}
        <div class="relative z-10">
            <p class="text-gray-700 text-xs">© {{ date('Y') }} ELTIZ. All rights reserved.</p>
        </div>
    </div>

    {{-- ══ RIGHT: Form Panel ══ --}}
    <div class="flex-1 flex flex-col items-center justify-center min-h-screen p-6 sm:p-10 bg-white">

        {{-- Mobile brand --}}
        <div class="lg:hidden text-center mb-8">
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

        <div class="w-full max-w-sm">

            {{-- Heading --}}
            <div class="mb-7">
                <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
                <p class="text-sm text-gray-400 mt-1">Sign in to continue</p>
            </div>

            {{-- Alerts --}}
            @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-5">
                @foreach($errors->all() as $error)
                    <p class="text-red-600 text-sm">{{ $error }}</p>
                @endforeach
            </div>
            @endif

            @if(session('status'))
            <div class="bg-green-50 border border-green-200 rounded-xl p-3 mb-5">
                <p class="text-green-700 text-sm">{{ session('status') }}</p>
            </div>
            @endif

            @if(session('google_pending'))
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-5 flex gap-2">
                <span class="text-lg leading-none">⏳</span>
                <p class="text-amber-800 text-sm">{{ session('google_pending') }}</p>
            </div>
            @endif

            {{-- Google OAuth Button --}}
            <a href="{{ route('auth.google') }}"
               class="flex items-center justify-center gap-3 w-full border border-gray-200 bg-white hover:bg-gray-50 active:scale-[.98] text-gray-700 font-medium py-3 rounded-xl transition-all text-sm mb-4 shadow-sm">
                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Continue with Google
            </a>

            {{-- Divider --}}
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-1 h-px bg-gray-100"></div>
                <span class="text-xs text-gray-400 font-medium">or sign in with email</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>

            {{-- Form --}}
            <form method="POST" action="/login" class="space-y-4">
                @csrf

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-300 focus:outline-none focus:bg-white focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400 transition-all text-sm"
                           placeholder="you@example.com">
                </div>

                {{-- Password --}}
                <div x-data="{ show: false }">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Password</label>
                        <a href="{{ route('password.request') }}" class="text-xs text-gray-400 hover:text-gray-700 transition-colors">Forgot?</a>
                    </div>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" name="password" required
                               class="w-full px-4 py-3 pr-11 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-300 focus:outline-none focus:bg-white focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400 transition-all text-sm"
                               placeholder="••••••••">
                        <button type="button" @click="show = !show"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-600 transition-colors">
                            <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Remember --}}
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="remember" id="remember"
                           class="w-4 h-4 rounded border-gray-300 accent-gray-900">
                    <label for="remember" class="text-sm text-gray-500 cursor-pointer">Remember me</label>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full bg-gray-900 hover:bg-gray-800 active:scale-[.98] text-white font-semibold py-3 rounded-xl transition-all text-sm tracking-wide mt-1">
                    Sign In &rarr;
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-gray-100 text-center">
                <p class="text-sm text-gray-400">
                    No account yet?
                    <a href="{{ route('register') }}" class="text-gray-900 font-semibold hover:underline underline-offset-2">Create one</a>
                </p>
            </div>
        </div>
    </div>

</body>
</html>
