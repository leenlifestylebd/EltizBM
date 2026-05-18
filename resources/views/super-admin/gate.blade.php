<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Access</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }
        .float { animation: float 4s ease-in-out infinite; }

        @keyframes pulse-ring {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { transform: scale(1); box-shadow: 0 0 0 12px rgba(239, 68, 68, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .pulse-ring { animation: pulse-ring 2.5s ease-out infinite; }

        body {
            background: #030712;
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(127, 29, 29, 0.08) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(30, 27, 75, 0.12) 0%, transparent 60%);
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4" x-data="{ showPass: false, loading: false }">

    {{-- Background grid --}}
    <div class="fixed inset-0 opacity-[0.03]" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 50px 50px;"></div>

    <div class="w-full max-w-sm relative z-10">

        {{-- Icon --}}
        <div class="flex justify-center mb-8">
            <div class="float">
                <div class="pulse-ring w-16 h-16 bg-red-950 rounded-2xl flex items-center justify-center border border-red-900/50">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Card --}}
        <div class="bg-gray-950/80 backdrop-blur border border-gray-800/60 rounded-3xl shadow-2xl overflow-hidden">

            {{-- Top accent bar --}}
            <div class="h-0.5 bg-gradient-to-r from-transparent via-red-600 to-transparent"></div>

            <div class="px-8 pt-8 pb-8">

                <div class="text-center mb-7">
                    <h1 class="text-xl font-bold text-white tracking-tight">Restricted Access</h1>
                    <p class="text-sm text-gray-600 mt-1">Authorized personnel only</p>
                </div>

                {{-- Error --}}
                @if($errors->any())
                <div class="mb-5 px-4 py-3 bg-red-950/60 border border-red-800/50 rounded-xl flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-red-400">{{ $errors->first() }}</p>
                </div>
                @endif

                <form method="POST" action="{{ url('/sa/' . $secret) }}" @submit="loading = true" class="space-y-4">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1.5 uppercase tracking-wider">Email</label>
                        <div class="relative">
                            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                                   placeholder="admin@email.com"
                                   class="w-full bg-gray-900 border border-gray-800 focus:border-red-700 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-gray-700 focus:outline-none transition-colors">
                        </div>
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1.5 uppercase tracking-wider">Password</label>
                        <div class="relative">
                            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <input :type="showPass ? 'text' : 'password'" name="password" required
                                   placeholder="••••••••"
                                   class="w-full bg-gray-900 border border-gray-800 focus:border-red-700 rounded-xl pl-10 pr-11 py-3 text-sm text-white placeholder-gray-700 focus:outline-none transition-colors">
                            <button type="button" @click="showPass = !showPass"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-400 transition-colors">
                                <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            class="w-full mt-2 py-3 bg-red-700 hover:bg-red-600 text-white font-semibold rounded-xl transition-all duration-200 flex items-center justify-center gap-2 shadow-lg shadow-red-950/50"
                            :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
                        <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span x-text="loading ? 'Verifying...' : 'Access Panel'"></span>
                    </button>
                </form>
            </div>

            {{-- Bottom bar --}}
            <div class="px-8 py-3 bg-gray-900/40 border-t border-gray-800/40 text-center">
                <p class="text-xs text-gray-700">Unauthorized access attempts are logged</p>
            </div>
        </div>

        {{-- Back to app --}}
        <div class="text-center mt-5">
            <a href="{{ route('login') }}" class="text-xs text-gray-700 hover:text-gray-500 transition-colors">
                ← Regular Login
            </a>
        </div>

    </div>
</body>
</html>
