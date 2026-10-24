@extends('layouts.app')
@section('title', 'Facebook review inbox')
@section('content')
<div class="max-w-4xl mx-auto space-y-5">
    <a href="{{ route('settings.automation') }}" class="text-primary-600">&larr; API & Automation</a>
    <a href="{{ route('settings.facebook.rules') }}" class="text-primary-600">Ad / Post product rules</a>
    <h1 class="text-xl font-semibold">Facebook review inbox</h1>
    <p class="text-sm text-gray-600 dark:text-slate-300">Review the customer message, edit the suggested reply, then approve sending. Comment replies are public. Messenger replies must be within the standard 24-hour window.</p>
    @if(session('success'))<p class="p-3 rounded bg-green-50 text-green-800">{{ session('success') }}</p>@endif
    @if(session('error'))<p class="p-3 rounded bg-red-50 text-red-800">{{ session('error') }}</p>@endif
    @if($errors->any())<p class="p-3 rounded bg-red-50 text-red-800">{{ $errors->first() }}</p>@endif
    @forelse($events as $event)
        <article class="p-5 border rounded-xl bg-white dark:bg-slate-800 dark:border-slate-700 space-y-3">
            <div class="flex justify-between gap-3 text-sm"><span>{{ ucfirst($event->kind) }} · {{ $event->created_at }}</span><strong>{{ ucfirst($event->status) }}</strong></div>
            <p class="whitespace-pre-wrap">{{ $event->payload['text'] ?: 'Non-text message — review in Meta Business Suite.' }}</p>
            @if($event->draft)<div class="p-3 bg-gray-50 dark:bg-slate-900 rounded-lg"><p class="text-sm font-semibold">{{ $event->status === 'sent' ? 'Original AI suggestion' : 'Suggested reply — not sent' }}</p><p class="whitespace-pre-wrap">{{ $event->draft }}</p></div>@endif
            @if($event->reason)<p class="text-sm text-gray-600 dark:text-slate-300">{{ $event->reason }}</p>@endif
            @if(!empty($event->payload['post_id']))<p class="text-xs">Post: {{ $event->payload['post_id'] }}</p>@endif
            @if(!empty($event->payload['ad_id']))<p class="text-xs">Ad: {{ $event->payload['ad_id'] }}</p>@endif
            @if($event->reply)
                <div class="p-3 border rounded"><strong>Reply: {{ $event->reply->status }}</strong><p class="whitespace-pre-wrap">{{ $event->reply->text }}</p>@if($event->reply->sent_at)<p>Sent: {{ $event->reply->sent_at }}</p>@endif @if($event->reply->note)<p>{{ $event->reply->note }}</p>@endif @if($event->reply->status === 'sending')<p>Sending or awaiting confirmation. If this remains pending, check Meta Business Suite before sending again.</p>@endif</div>
            @endif
            @if(in_array($event->status, ['review','handover']) && (!$event->reply || $event->reply->status === 'failed') && !str_starts_with($event->payload['external_id'] ?? '', 'setup-test-'))
                <form method="POST" action="{{ route('settings.facebook.reply', $event->id) }}" class="space-y-2">
                    @csrf
                    <label class="block font-semibold" for="reply-{{ $event->id }}">{{ $event->kind === 'comment' ? 'Public comment reply' : 'Messenger reply' }}</label>
                    <textarea id="reply-{{ $event->id }}" name="reply" required maxlength="2000" rows="3" class="w-full border rounded p-3 dark:bg-slate-900">{{ $event->reply?->text ?? $event->draft }}</textarea>
                    <button type="submit" class="px-4 py-2 rounded bg-primary-600 text-white">Approve &amp; Send {{ $event->kind === 'comment' ? 'public reply' : 'reply' }}</button>
                </form>
            @endif
        </article>
    @empty
        <div class="p-5 border rounded-xl">No Facebook events received yet. Complete Meta webhook setup and enable intake, then send a test message from a permitted test account.</div>
    @endforelse
    {{ $events->links() }}
</div>
@endsection
