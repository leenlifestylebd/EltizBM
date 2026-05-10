<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fulfillo — Sign In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-gray-950 flex items-center justify-center p-4">

    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <svg class="w-8 h-8 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">Fulfillo</h1>
            <p class="text-gray-500 text-sm mt-1">Business Management Platform</p>
        </div>

        {{-- Card --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 shadow-2xl">
            <h2 class="text-xl font-semibold text-white mb-1">Welcome back</h2>
            <p class="text-gray-500 text-sm mb-6">Sign in to your account</p>

            @if($errors->any())
            <div class="bg-red-950 border border-red-800 rounded-xl p-3 mb-4">
                @foreach($errors->all() as $error)
                    <p class="text-red-400 text-sm">{{ $error }}</p>
                @endforeach
            </div>
            @endif

            @if(session('status'))
            <div class="bg-green-950 border border-green-800 rounded-xl p-3 mb-4">
                <p class="text-green-400 text-sm">{{ session('status') }}</p>
            </div>
            @endif

            <form method="POST" action="/login" x-data="{ showPass: false }">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1.5">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-gray-600 transition-all text-sm"
                               placeholder="you@example.com">
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-gray-400">Password</label>
                            <a href="{{ route('password.request') }}" class="text-xs text-gray-500 hover:text-white transition-colors">Forgot password?</a>
                        </div>
                        <div class="relative" x-data="{ showPass: false }">
                            <input :type="showPass ? 'text' : 'password'" name="password" required
                                   class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-gray-600 transition-all text-sm pr-10"
                                   placeholder="••••••••">
                            <button type="button" @click="showPass = !showPass"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                                <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="remember" id="remember"
                               class="w-4 h-4 rounded bg-gray-800 border-gray-600 text-white">
                        <label for="remember" class="text-sm text-gray-400">Remember me</label>
                    </div>

                    <button type="submit"
                            class="w-full bg-white hover:bg-gray-100 text-gray-900 font-semibold py-3 rounded-xl transition-all text-sm">
                        Sign In
                    </button>
                </div>
            </form>

            <p class="text-center text-sm text-gray-600 mt-6">
                Don't have an account?
                <a href="{{ route('register') }}" class="text-gray-400 hover:text-white transition-colors font-medium">Create one</a>
            </p>
        </div>

        <p class="text-center text-xs text-gray-700 mt-6">© {{ date('Y') }} Fulfillo. All rights reserved.</p>
    </div>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>