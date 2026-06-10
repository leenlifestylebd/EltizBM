@extends('layouts.app')
@section('title', 'Expenses')
@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Expenses</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">৳{{ number_format($totalAmount) }} total</p>
    </div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 h-fit">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Add Expense</h3>
        <form method="POST" action="{{ route('accounts.expenses.store') }}" class="space-y-3">
            @csrf
            <div><label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Title <span class="text-red-500">*</span></label>
            <input type="text" name="title" required class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="e.g. Rent, Electricity..."></div>
            <div><label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Amount <span class="text-red-500">*</span></label>
            <div class="relative"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">৳</span>
            <input type="number" name="amount" required min="0" step="0.01" class="w-full pl-7 pr-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"></div></div>
            <div x-data="quickCat('expense')" @cat-created.window="onCreated($event.detail)">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300">Category</label>
                    <button type="button" @click="open = !open"
                            class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-medium">
                        <span x-text="open ? '✕ Cancel' : '+ New'"></span>
                    </button>
                </div>
                <select name="category_id" x-ref="select"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">Select category</option>
                    @foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach
                </select>
                <div x-show="open" x-cloak x-transition class="mt-2 flex gap-2">
                    <input type="text" x-model="newName" @keydown.enter.prevent="save()"
                           placeholder="New category name..."
                           class="flex-1 px-3 py-2 text-sm border border-primary-300 dark:border-primary-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <button type="button" @click="save()" :disabled="saving"
                            class="px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-lg transition-colors disabled:opacity-50">
                        <span x-text="saving ? '...' : 'Save'"></span>
                    </button>
                </div>
                <p x-show="error" x-text="error" class="text-xs text-red-500 mt-1"></p>
            </div>
            <div><label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Payment Method</label>
            <select name="payment_method" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                @foreach(['cash','bkash','nagad','rocket','bank','card','other'] as $m)<option value="{{ $m }}">{{ ucfirst($m) }}</option>@endforeach
            </select></div>
            <div><label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Date <span class="text-red-500">*</span></label>
            <input type="date" name="transaction_date" required value="{{ today()->format('Y-m-d') }}" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"></div>
            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">Add Expense</button>
        </form>
    </div>
    <div class="lg:col-span-2">
        <form method="GET" class="flex gap-2 mb-3">
            <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}" class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
            <select name="category" class="flex-1 text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Categories</option>
                @foreach($categories as $cat)<option value="{{ $cat->id }}" {{ request('category')==$cat->id?'selected':'' }}>{{ $cat->name }}</option>@endforeach
            </select>
            <button type="submit" class="px-3 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm transition-colors">Filter</button>
        </form>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($transactions as $txn)
                <div class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors group">
                    <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $txn->title }}</p>
                        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $txn->category?->name ?? '—' }} · {{ $txn->transaction_date->format('d M Y') }} · {{ ucfirst($txn->payment_method) }}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-sm font-bold text-red-600 dark:text-red-400">-৳{{ number_format($txn->amount) }}</span>
                        <form method="POST" action="{{ route('accounts.transactions.destroy', $txn) }}" onsubmit="return confirm('Delete?')" class="opacity-0 group-hover:opacity-100 transition-opacity">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1 text-gray-300 hover:text-red-500 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="px-4 py-12 text-center text-sm text-gray-400 dark:text-slate-500">No expense records</div>
                @endforelse
            </div>
            @if($transactions->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $transactions->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
</div>
@push('scripts')
<script>
function quickCat(type) {
    return {
        open: false, newName: '', saving: false, error: '',
        save() {
            if (!this.newName.trim()) return;
            this.saving = true; this.error = '';
            fetch('{{ route("settings.account-categories.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ name: this.newName.trim(), type }),
            })
            .then(r => r.json().then(d => ({ ok: r.ok, d })))
            .then(({ ok, d }) => {
                if (!ok) { this.error = d.error || 'Failed to create'; return; }
                this.$dispatch('cat-created', { id: d.id, name: d.name, type });
                this.newName = ''; this.open = false;
            })
            .catch(() => { this.error = 'Network error. Try again.'; })
            .finally(() => this.saving = false);
        },
        onCreated(detail) {
            if (detail.type !== type) return;
            const opt = new Option(detail.name, detail.id, true, true);
            this.$refs.select.appendChild(opt);
        }
    }
}
</script>
@endpush

@endsection
