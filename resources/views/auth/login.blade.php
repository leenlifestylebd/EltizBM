@extends('layouts.auth')
@section('title', 'Login')

@section('content')
<h2 class="text-xl font-semibold text-gray-900 mb-1">Welcome back</h2>
<p class="text-sm text-gray-500 mb-6">Sign in to your account</p>

@if($errors->any())
<div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
    <ul class="text-xs text-red-600 space-y-1">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('login') }}" x-data="{ showPass: false }">
    @csrf

    <div class="mb-4">
        <label class="block text-xs font-medium text-gray-700 mb-1.5">Email Address</label>
        <input type="email" name="email" value="{{ old('email') }}" required
               class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
               placeholder="you@example.com">
    </div>

    <div class="mb-4">
        <div class="flex items-center justify-between mb-1.5">
            <label class="text-xs font-medium text-gray-700">Password</label>
            <a href="{{ route('password.request') }}" class="text-xs text-blue-600 hover:underline">Forgot password?</a>
        </div>
        <div class="relative">
            :type="showPass ? 'text' : 'password'"
            <input type="password" name="password" required
                   class="w-full px-3 py-2.5 pr-10 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                   placeholder="••••••••">
            <button type="button" @click="showPass = !showPass"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <svg x-show="showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="flex items-center mb-5">
        <input type="checkbox" name="remember" id="remember" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
        <label for="remember" class="ml-2 text-xs text-gray-600">Remember me</label>
    </div>

    <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
        Sign In
    </button>
</form>

<p class="text-center text-xs text-gray-500 mt-5">
    Don't have an account?
    <a href="{{ route('register') }}" class="text-blue-600 font-medium hover:underline">Create one</a>
</p>
@endsection
