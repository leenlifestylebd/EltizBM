@extends('layouts.auth')
@section('title', 'Register')

@section('content')
<h2 class="text-xl font-semibold text-gray-900 mb-1">Create account</h2>
<p class="text-sm text-gray-500 mb-6">Start your free 14-day trial</p>

@if($errors->any())
<div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
    <ul class="text-xs text-red-600 space-y-1">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('register') }}" x-data="{ showPass: false }">
    @csrf

    <div class="mb-3">
        <label class="block text-xs font-medium text-gray-700 mb-1.5">Full Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required
               class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
               placeholder="Al Mamun">
    </div>

    <div class="mb-3">
        <label class="block text-xs font-medium text-gray-700 mb-1.5">Business Name</label>
        <input type="text" name="business_name" value="{{ old('business_name') }}" required
               class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
               placeholder="My Business">
    </div>

    <div class="mb-3">
        <label class="block text-xs font-medium text-gray-700 mb-1.5">Email Address</label>
        <input type="email" name="email" value="{{ old('email') }}" required
               class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
               placeholder="you@example.com">
    </div>

    <div class="mb-3">
        <label class="block text-xs font-medium text-gray-700 mb-1.5">Phone</label>
        <input type="text" name="phone" value="{{ old('phone') }}"
               class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
               placeholder="01XXXXXXXXX">
    </div>

    <div class="mb-5">
        <label class="block text-xs font-medium text-gray-700 mb-1.5">Password</label>
        <div class="relative">
            <input :type="showPass ? 'text' : 'password'" name="password" required
                   class="w-full px-3 py-2.5 pr-10 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   placeholder="Min 8 characters">
            <button type="button" @click="showPass = !showPass"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </button>
        </div>
        <input type="password" name="password_confirmation" required
               class="w-full mt-2 px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
               placeholder="Confirm password">
    </div>

    <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
        Create Account
    </button>
</form>

<p class="text-center text-xs text-gray-500 mt-5">
    Already have an account?
    <a href="{{ route('login') }}" class="text-blue-600 font-medium hover:underline">Sign in</a>
</p>
@endsection
