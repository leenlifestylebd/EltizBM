@extends('layouts.app')
@section('title', 'Facebook review inbox')
@section('content')
<div class="max-w-4xl mx-auto space-y-5">
    <a href="{{ route('settings.automation') }}" class="text-primary-600">&larr; API & Automation</a>
    <h1 class="text-xl font-semibold">Facebook review inbox</h1>
    <p class="text-sm text-gray-600 dark:text-slate-300">Review mode: replies are suggestions only. No message is sent from this inbox. Handle approved replies or handovers in Meta Business Suite for now.</p>
    @forelse($events as $event)
        <article class="p-5 border rounded-xl bg-white dark:bg-slate-800 dark:border-slate-700 space-y-3">
            <div class="flex justify-between gap-3 text-sm"><span>{{ ucfirst($event->kind) }} · {{ $event->created_at }}</span><strong>{{ ucfirst($event->status) }}</strong></div>
            <p class="whitespace-pre-wrap">{{ $event->payload['text'] ?: 'Non-text message — review in Meta Business Suite.' }}</p>
            @if($event->draft)<div class="p-3 bg-gray-50 dark:bg-slate-900 rounded-lg"><p class="text-sm font-semibold">Suggested reply — not sent</p><p class="whitespace-pre-wrap">{{ $event->draft }}</p></div>@endif
            @if($event->reason)<p class="text-sm text-gray-600 dark:text-slate-300">{{ $event->reason }}</p>@endif
            @if(!empty($event->payload['post_id']))<p class="text-xs">Post: {{ $event->payload['post_id'] }}</p>@endif
            @if(!empty($event->payload['ad_id']))<p class="text-xs">Ad: {{ $event->payload['ad_id'] }} — product mapping requires verification.</p>@endif
        </article>
    @empty
        <div class="p-5 border rounded-xl">No Facebook events received yet. Complete Meta webhook setup and enable intake, then send a test message from a permitted test account.</div>
    @endforelse
    {{ $events->links() }}
</div>
@endsection
