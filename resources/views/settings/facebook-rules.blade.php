@extends('layouts.app')
@section('title','Facebook product rules')
@section('content')
<div class="max-w-4xl mx-auto space-y-5">
<a href="{{ route('settings.facebook.inbox') }}">&larr; Review inbox</a>
<h1 class="text-xl font-semibold">Facebook Ad / Post product rules</h1>
<p>Link a numeric Ad ID or Page Post ID to one ERP product. Automatic replies use the current ERP price and stock only. Other questions remain for human review.</p>
@if(session('success'))<p>{{ session('success') }}</p>@endif
@if($errors->any())<p class="text-red-600">{{ $errors->first() }}</p>@endif
<form method="POST" action="{{ route('settings.facebook.rules.store') }}" class="space-y-3 p-4 border rounded">
@csrf
<label class="block">Source <select name="source_type" class="border rounded dark:bg-slate-900"><option value="post">Page post</option><option value="ad">Ad</option></select></label>
<label class="block">Ad ID / Page Post ID <input name="external_id" required maxlength="100" placeholder="123456 or PAGEID_POSTID" class="border rounded p-2 dark:bg-slate-900"></label>
<label class="block">Product <select name="product_id" required class="border rounded p-2 dark:bg-slate-900">@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} {{ $p->sku }}</option>@endforeach</select></label>
<label class="block"><input type="checkbox" name="auto_reply" value="1"> Enable automatic price / stock replies for this rule</label>
<p class="text-sm">Save the same source and ID again to update its product or turn automatic replies off. Ad IDs are used only when Facebook supplies an ad referral.</p>
<button class="px-4 py-2 bg-primary-600 text-white rounded">Save rule</button>
</form>
@forelse($rules as $rule)<div class="p-4 border rounded"><p>{{ ucfirst($rule->source_type) }} {{ $rule->external_id }} → {{ $rule->product?->name }} · {{ $rule->auto_reply ? 'Automatic price/stock replies ON' : 'Review only' }}</p><form method="POST" action="{{ route('settings.facebook.rules.destroy',$rule->id) }}">@csrf @method('DELETE')<button class="text-red-600">Remove rule</button></form></div>@empty<p>No product rules yet.</p>@endforelse
</div>
@endsection
