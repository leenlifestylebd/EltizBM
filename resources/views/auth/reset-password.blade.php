<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ELTIZ — Set New Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-white flex flex-col items-center justify-center p-6">
    <div class="w-full max-w-sm">

        {{-- Brand --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 mb-1">
                <div class="w-7 h-7 bg-gray-900 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 4a1 1 0 011-1h14a1 1 0 010 2H3a1 1 0 01-1-1zM2 9a1 1 0 011-1h9a1 1 0 010 2H3a1 1 0 01-1-1zM2 14a1 1 0 011-1h14a1 1 0 010 2H3a1 1 0 01-1-1z"/>
                    </svg>
                </div>
                <span class="text-xl font-black text-gray-900 tracking-tight">ELTIZ</span>
            </div>
        </div>

        <div class="mb-7">
            <h1 class="text-2xl font-bold text-gray-900">Set a new password</h1>
            <p class="text-sm text-gray-400 mt-1">Choose a strong password you'll remember.</p>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-5">
            @foreach($errors->all() as $error)
                <p class="text-red-600 text-sm">{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4" x-data="{ show: false }">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Email</label>
                <input type="email" name="email" value="{{ old('email', $email ?? '') }}" required
                       class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-300 focus:outline-none focus:bg-white focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400 transition-all text-sm"
                       placeholder="you@example.com">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">New password</label>
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

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Confirm password</label>
                <input type="password" name="password_confirmation" required
                       class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-300 focus:outline-none focus:bg-white focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400 transition-all text-sm"
                       placeholder="••••••••">
            </div>

            <button type="submit"
                    class="w-full bg-gray-900 hover:bg-gray-800 active:scale-[.98] text-white font-semibold py-3 rounded-xl transition-all text-sm tracking-wide">
                Reset password
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-100 text-center">
            <a href="{{ route('login') }}" class="text-sm text-gray-900 font-semibold hover:underline underline-offset-2">&larr; Back to sign in</a>
        </div>
    </div>
</body>
</html>
