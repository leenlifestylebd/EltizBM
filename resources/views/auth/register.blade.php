@extends('layouts.auth')
@section('title', 'Create Account')

@section('content')

<div class="mb-7">
    <h1 class="text-2xl font-bold text-gray-900">Create account</h1>
    <p class="text-sm text-gray-400 mt-1">Start your free 14-day trial</p>
</div>

@if($errors->any())
<div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-5">
    <ul class="space-y-1">
        @foreach($errors->all() as $error)
            <li class="text-red-600 text-sm">{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Google OAuth --}}
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

<div class="flex items-center gap-3 mb-4">
    <div class="flex-1 h-px bg-gray-100"></div>
    <span class="text-xs text-gray-400 font-medium">or register with email</span>
    <div class="flex-1 h-px bg-gray-100"></div>
</div>

<form method="POST" action="{{ route('register') }}" x-data="{ show: false }" class="space-y-3.5">
    @csrf

    {{-- Full Name --}}
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Full Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required
               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-300 focus:outline-none focus:bg-white focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400 transition-all text-sm"
               placeholder="Al Mamun">
    </div>

    {{-- Business Name --}}
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Business Name</label>
        <input type="text" name="business_name" value="{{ old('business_name') }}" required
               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-300 focus:outline-none focus:bg-white focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400 transition-all text-sm"
               placeholder="My Business">
    </div>

    {{-- Email --}}
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required
               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-300 focus:outline-none focus:bg-white focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400 transition-all text-sm"
               placeholder="you@example.com">
    </div>

    {{-- Phone --}}
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Phone</label>
        <input type="text" name="phone" value="{{ old('phone') }}"
               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-300 focus:outline-none focus:bg-white focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400 transition-all text-sm"
               placeholder="01XXXXXXXXX">
    </div>

    {{-- Password --}}
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Password</label>
        <div class="relative">
            <input :type="show ? 'text' : 'password'" name="password" required
                   class="w-full px-4 py-3 pr-11 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-300 focus:outline-none focus:bg-white focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400 transition-all text-sm"
                   placeholder="Min 8 characters">
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

    {{-- Confirm Password --}}
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Confirm Password</label>
        <input type="password" name="password_confirmation" required
               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-300 focus:outline-none focus:bg-white focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400 transition-all text-sm"
               placeholder="Repeat password">
    </div>

    <button type="submit"
            class="w-full bg-gray-900 hover:bg-gray-800 active:scale-[.98] text-white font-semibold py-3 rounded-xl transition-all text-sm tracking-wide mt-1">
        Create Account &rarr;
    </button>
</form>

<div class="mt-6 pt-6 border-t border-gray-100 text-center">
    <p class="text-sm text-gray-400">
        Already have an account?
        <a href="{{ route('login') }}" class="text-gray-900 font-semibold hover:underline underline-offset-2">Sign in</a>
    </p>
</div>

@endsection
