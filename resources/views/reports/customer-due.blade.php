@extends('layouts.app')
@section('title', 'Customer Due')

@section('content')
@php
    // Ageing helper: how long the oldest unpaid order has been outstanding decides
    // the badge colour, so the worst debts stand out without reading numbers.
    $ageOf = function ($c) {
        if (!$c->oldest_unpaid_at) return null;
        return (int) \Carbon\Carbon::parse($c->oldest_unpaid_at)->diffInDays(now());
    };
    $bandOf = function (?int $d) {
        if ($d === null)  return ['—',        'gray'];
        if ($d <= 30)     return [$d.'d',     'emerald'];
        if ($d <= 60)     return [$d.'d',     'amber'];
        return [$d.'d', 'red'];
    };
    $qs = ['search' => $search ?? '', 'sort' => $sort ?? 'due'];
@endphp

{{-- ── Header ─────────────────────────────────────────────── --}}
<div class="flex items-start justify-between gap-3 mb-4">
    <div class="flex items-center gap-2">
        <a href="{{ route('reports.all') }}" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-slate-300">Reports</a>
        <span class="text-gray-300 dark:text-slate-600">/</span>
        <span class="text-xs font-medium text-gray-700 dark:text-slate-300">Customer Due</span>
    </div>
    <div x-data="{ open: false }" @click.outside="open = false" class="relative shrink-0">
        <button @click="open = !open" class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-300 shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export
        </button>
        <div x-show="open" x-cloak x-transition class="absolute right-0 top-10 z-30 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-600 shadow-lg p-1 min-w-36">
            <a href="{{ route('reports.export','customer-due') }}?format=csv" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
            <a href="{{ route('reports.export','customer-due') }}?format=pdf" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
        </div>
    </div>
</div>

{{-- ── KPI cards ──────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div class="bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/30 dark:to-rose-900/10 rounded-xl p-4 border border-red-100 dark:border-red-900/40">
        <p class="text-xs text-red-600 dark:text-red-400 font-medium mb-1">মোট বকেয়া</p>
        <p class="text-2xl font-bold text-red-700 dark:text-red-300">৳{{ number_format($totalDue) }}</p>
        <p class="text-xs text-red-500/70 mt-1">{{ $dueCustomerCount }} জন কাস্টমার</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-emerald-100 dark:border-emerald-900/40">
        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">০–৩০ দিন</p>
        <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">৳{{ number_format($aging['current']) }}</p>
        <p class="text-xs text-gray-400 mt-1">সাম্প্রতিক</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-amber-100 dark:border-amber-900/40">
        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">৩১–৬০ দিন</p>
        <p class="text-xl font-bold text-amber-600 dark:text-amber-400">৳{{ number_format($aging['warn']) }}</p>
        <p class="text-xs text-gray-400 mt-1">তাগাদা দিন</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-red-100 dark:border-red-900/40">
        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-1">৬০+ দিন</p>
        <p class="text-xl font-bold text-red-600 dark:text-red-400">৳{{ number_format($aging['overdue']) }}</p>
        <p class="text-xs text-gray-400 mt-1">ঝুঁকিপূর্ণ</p>
    </div>
</div>

{{-- ── Search + sort ──────────────────────────────────────── --}}
<form method="GET" class="flex flex-wrap items-center gap-2 mb-4">
    <div class="relative flex-1 min-w-[180px]">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" name="search" value="{{ $search }}" placeholder="নাম বা ফোন দিয়ে খুঁজুন..."
               class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
    </div>
    <select name="sort" onchange="this.form.submit()"
            class="px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
        <option value="due"    @selected($sort === 'due')>সর্বোচ্চ বকেয়া</option>
        <option value="oldest" @selected($sort === 'oldest')>সবচেয়ে পুরনো</option>
        <option value="name"   @selected($sort === 'name')>নাম (A–Z)</option>
    </select>
    <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">Filter</button>
    @if($search)
        <a href="{{ route('reports.customer-due') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-slate-400">✕ Clear</a>
    @endif
</form>

{{-- ── Desktop table ──────────────────────────────────────── --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Customer</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">বকেয়া অর্ডার</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">পুরনো</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">মোট কেনা</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">বকেয়া</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-slate-400">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($customers as $c)
                @php [$ageLabel, $ageColor] = $bandOf($ageOf($c)); @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors group">
                    <td class="px-4 py-3">
                        <a href="{{ route('people.customers.show', $c) }}" class="font-medium text-gray-900 dark:text-white hover:text-primary-600 hover:underline">{{ $c->name }}</a>
                        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $c->phone ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center justify-center min-w-[1.75rem] px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300">{{ $c->unpaid_orders_count }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-{{ $ageColor }}-50 dark:bg-{{ $ageColor }}-900/30 text-{{ $ageColor }}-700 dark:text-{{ $ageColor }}-400">{{ $ageLabel }}</span>
                    </td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-slate-300">৳{{ number_format($c->total_purchase) }}</td>
                    <td class="px-4 py-3 text-right font-bold text-red-600 dark:text-red-400">৳{{ number_format($c->due_amount) }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-1.5">
                            @if($c->phone)
                            <a href="tel:{{ $c->phone }}" title="কল করুন"
                               class="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg opacity-0 group-hover:opacity-100 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </a>
                            <a href="https://wa.me/88{{ preg_replace('/\D/', '', $c->phone) }}" target="_blank" rel="noopener" title="WhatsApp"
                               class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg opacity-0 group-hover:opacity-100 transition-all">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884a9.82 9.82 0 016.988 2.898 9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                            </a>
                            @endif
                            <button type="button"
                                    @click="$dispatch('open-collect-due', { id: {{ $c->id }}, name: @js($c->name), due: {{ $c->due_amount }} })"
                                    class="px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                                Collect
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-16 text-center">
                    <p class="text-3xl mb-2">🎉</p>
                    <p class="text-sm text-gray-400 dark:text-slate-500">কোনো বকেয়া নেই</p>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Mobile cards ───────────────────────────────────── --}}
    <div class="md:hidden divide-y divide-gray-50 dark:divide-slate-700">
        @forelse($customers as $c)
        @php [$ageLabel, $ageColor] = $bandOf($ageOf($c)); @endphp
        <div class="p-4">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="min-w-0">
                    <a href="{{ route('people.customers.show', $c) }}" class="font-semibold text-gray-900 dark:text-white text-sm">{{ $c->name }}</a>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $c->phone ?? '—' }}</p>
                </div>
                <div class="text-right shrink-0">
                    <p class="font-bold text-red-600 dark:text-red-400">৳{{ number_format($c->due_amount) }}</p>
                    <p class="text-xs text-gray-400">মোট ৳{{ number_format($c->total_purchase) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5 mb-3 flex-wrap">
                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300">{{ $c->unpaid_orders_count }} অর্ডার</span>
                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-{{ $ageColor }}-50 dark:bg-{{ $ageColor }}-900/30 text-{{ $ageColor }}-700 dark:text-{{ $ageColor }}-400">{{ $ageLabel }} পুরনো</span>
            </div>
            <div class="flex items-center gap-2">
                @if($c->phone)
                <a href="tel:{{ $c->phone }}" class="flex-1 text-center py-2 text-xs font-medium border border-gray-200 dark:border-slate-600 text-gray-600 dark:text-slate-300 rounded-lg">📞 কল</a>
                <a href="https://wa.me/88{{ preg_replace('/\D/', '', $c->phone) }}" target="_blank" rel="noopener" class="flex-1 text-center py-2 text-xs font-medium border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 rounded-lg">WhatsApp</a>
                @endif
                <button type="button"
                        @click="$dispatch('open-collect-due', { id: {{ $c->id }}, name: @js($c->name), due: {{ $c->due_amount }} })"
                        class="flex-1 py-2 text-xs font-semibold text-white bg-emerald-600 rounded-lg">Collect</button>
            </div>
        </div>
        @empty
        <div class="p-12 text-center">
            <p class="text-3xl mb-2">🎉</p>
            <p class="text-sm text-gray-400 dark:text-slate-500">কোনো বকেয়া নেই</p>
        </div>
        @endforelse
    </div>

    @if($customers->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $customers->links() }}</div>
    @endif
</div>

{{-- ── Collect Due Modal ──────────────────────────────────── --}}
<div x-data="{ open: false, c: { id: null, name: '', due: 0 } }"
     @open-collect-due.window="c = $event.detail; open = true"
     @keydown.escape.window="open = false">
    <div x-show="open" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-4">
        <div @click.outside="open = false" class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-sm">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">টাকা আদায় — <span x-text="c.name"></span></h3>
                <button @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <form :action="`{{ url('people/customers') }}/${c.id}/collect-due`" method="POST" class="p-5 space-y-4">
                @csrf
                <p class="text-xs text-gray-500 dark:text-slate-400">বকেয়া: <span class="font-semibold text-red-500">৳<span x-text="Number(c.due).toLocaleString()"></span></span></p>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Amount</label>
                    <input type="number" step="0.01" min="0.01" :max="c.due" name="amount" :value="c.due" required
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Method</label>
                    <select name="payment_method"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        @foreach(['cash'=>'Cash','bkash'=>'bKash','nagad'=>'Nagad','rocket'=>'Rocket','bank'=>'Bank','card'=>'Card'] as $k => $label)
                        <option value="{{ $k }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Date</label>
                    <input type="date" name="payment_date" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <p class="text-xs text-gray-400 dark:text-slate-500">পুরনো অর্ডার থেকে ধারাবাহিকভাবে সমন্বয় হবে। আগের দিনের হলে তারিখ বদলে দিন।</p>
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-xl text-sm transition-colors">
                    আদায় নিশ্চিত করুন
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
