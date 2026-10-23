@extends('layouts.app')
@section('title', 'Choose Facebook Page')
@section('content')
<div class="max-w-2xl mx-auto space-y-5">
    <a href="{{ route('settings.automation') }}" class="text-primary-600 dark:text-primary-400">&larr; Back to settings</a>
    <section class="p-6 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 space-y-4">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Choose a Facebook Page</h1>
        <p class="text-sm text-gray-600 dark:text-slate-300">Connect one Page to {{ $business->name }}. This replaces the Page connection for this business only. No messages will be sent.</p>
        <form method="POST" action="{{ route('settings.facebook.select') }}" class="space-y-4">
            @csrf
            <fieldset class="space-y-3">
                <legend class="sr-only">Available Facebook Pages</legend>
                @foreach($pages as $page)
                    <label class="flex gap-3 items-start p-4 border rounded-lg border-gray-200 dark:border-slate-600 cursor-pointer">
                        <input type="radio" name="page_id" value="{{ $page['id'] }}" required class="mt-1">
                        <span><span class="block font-medium">{{ $page['name'] }}</span><span class="text-sm text-gray-600 dark:text-slate-300">Page ID: {{ $page['id'] }}</span></span>
                    </label>
                @endforeach
            </fieldset>
            <button class="px-4 py-3 rounded-lg bg-blue-600 text-white font-medium">Connect selected Page</button>
        </form>
        <p class="text-sm text-gray-600 dark:text-slate-300">This selection expires after 10 minutes. If a Page is missing, reconnect and check the permissions and Pages you allowed on Facebook.</p>
    </section>
</div>
@endsection
