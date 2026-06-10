@extends('super-admin.layout')
@section('title', 'All Businesses')

@section('content')

<div class="mb-6 flex items-center justify-between gap-4 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold text-white">All Businesses</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $businesses->total() }} total</p>
    </div>
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..."
               class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-blue-500 w-64">
        <button type="submit" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white text-sm rounded-xl transition-colors">Search</button>
    </form>
</div>

{{-- Reset Modal (one global modal, Alpine fills in business data) --}}
<div x-data="resetModal()" x-cloak>
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
         @click.self="open = false">

        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="w-full max-w-lg bg-gray-900 border border-orange-500/30 rounded-2xl shadow-2xl overflow-hidden">

            {{-- Modal Header --}}
            <div class="px-6 py-4 bg-orange-950/50 border-b border-orange-500/20 flex items-center gap-3">
                <div class="w-9 h-9 bg-orange-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-white text-sm">Reset Business Data</h3>
                    <p class="text-xs text-orange-400/80" x-text="bizName"></p>
                </div>
                <button @click="open = false" class="ml-auto text-gray-500 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="formAction" method="POST" class="px-6 py-5">
                @csrf

                {{-- Module checkboxes --}}
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Select data to clear:</p>
                <div class="grid grid-cols-2 gap-2 mb-5">
                    @php
                    $modules = [
                        ['orders',      '🛒', 'Orders & Items',      'All sales orders'],
                        ['products',    '📦', 'Products',            'All products & variants'],
                        ['customers',   '👥', 'Customers',           'Customer list'],
                        ['transactions','💰', 'Transactions',        'Account transactions'],
                        ['employees',   '👷', 'Employees',           'Staff & payments'],
                        ['production',  '🏭', 'Production',          'Entries & tasks'],
                        ['inventory',   '🏪', 'Inventory',           'Stock adjustments'],
                        ['categories',  '🏷️', 'Categories & Brands', 'Product categories'],
                    ];
                    @endphp
                    @foreach($modules as [$key, $icon, $label, $desc])
                    <label class="flex items-start gap-2.5 p-3 bg-gray-800/60 hover:bg-gray-800 border border-gray-700 hover:border-orange-500/50 rounded-xl cursor-pointer transition-all group">
                        <input type="checkbox" name="modules[]" value="{{ $key }}"
                               class="mt-0.5 w-4 h-4 rounded accent-orange-500 cursor-pointer flex-shrink-0">
                        <div>
                            <p class="text-sm text-white font-medium">{{ $icon }} {{ $label }}</p>
                            <p class="text-xs text-gray-500">{{ $desc }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>

                {{-- Select All / None --}}
                <div class="flex gap-3 mb-5">
                    <button type="button" onclick="document.querySelectorAll('[name=\'modules[]\']').forEach(c=>c.checked=true)"
                            class="text-xs text-orange-400 hover:text-orange-300 underline underline-offset-2">
                        Select All
                    </button>
                    <button type="button" onclick="document.querySelectorAll('[name=\'modules[]\']').forEach(c=>c.checked=false)"
                            class="text-xs text-gray-500 hover:text-gray-300 underline underline-offset-2">
                        Clear All
                    </button>
                </div>

                {{-- Confirm by typing name --}}
                <div class="bg-red-950/40 border border-red-700/40 rounded-xl p-4 mb-5">
                    <p class="text-xs text-red-400 mb-2">
                        ⚠️ This action is <strong>irreversible</strong>. Type the business name to confirm:
                    </p>
                    <p class="text-sm font-mono font-bold text-white mb-2" x-text="bizName"></p>
                    <input type="text" name="confirm_name" required autocomplete="off"
                           :placeholder="bizName"
                           class="w-full bg-gray-900 border border-red-700/50 focus:border-red-500 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none">
                </div>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="button" @click="open = false"
                            class="flex-1 px-4 py-2.5 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-medium rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold rounded-xl transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Reset Selected Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Businesses Table --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="px-5 py-3 text-left">Business</th>
                        <th class="px-5 py-3 text-left">Contact</th>
                        <th class="px-4 py-3 text-center">Users</th>
                        <th class="px-4 py-3 text-center">Products</th>
                        <th class="px-4 py-3 text-center">Orders</th>
                        <th class="px-5 py-3 text-left">Trial / Plan</th>
                        <th class="px-5 py-3 text-left">Created</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($businesses as $biz)
                    <tr class="hover:bg-gray-800/30 transition-colors" x-data="{ extendOpen: false }">
                        <td class="px-5 py-3">
                            <p class="font-medium text-white">{{ $biz->name }}</p>
                            <p class="text-xs text-gray-600">ID: {{ $biz->id }}</p>
                        </td>
                        <td class="px-5 py-3">
                            <p class="text-gray-300 text-xs">{{ $biz->email }}</p>
                            <p class="text-gray-600 text-xs">{{ $biz->phone }}</p>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-white font-semibold">{{ $biz->users_count }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-gray-400">{{ $biz->products_count }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-gray-400">{{ $biz->orders_count }}</span>
                        </td>
                        <td class="px-5 py-3">
                            @if($biz->trial_ends_at && $biz->trial_ends_at->isFuture())
                                <span class="text-xs bg-yellow-900/50 text-yellow-400 px-2 py-0.5 rounded-full block w-fit">
                                    Trial: {{ $biz->trialDaysLeft() }}d left
                                </span>
                            @elseif($biz->trial_ends_at)
                                <span class="text-xs bg-red-900/50 text-red-400 px-2 py-0.5 rounded-full block w-fit">Trial ended</span>
                            @else
                                <span class="text-xs text-gray-600">No trial</span>
                            @endif
                            <p class="text-xs text-gray-600 mt-0.5">{{ $biz->plan ?? 'basic' }}</p>
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-500">
                            {{ $biz->created_at->format('d M Y') }}
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1.5 flex-wrap">

                                {{-- Toggle Active --}}
                                <form method="POST" action="{{ route('super-admin.businesses.toggle', $biz) }}">
                                    @csrf
                                    <button type="submit" title="Toggle active/inactive"
                                            class="px-2 py-1 text-xs bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors">
                                        Toggle
                                    </button>
                                </form>

                                {{-- Extend Trial --}}
                                <button @click="extendOpen = !extendOpen"
                                        class="px-2 py-1 text-xs bg-yellow-900/50 hover:bg-yellow-800/50 text-yellow-400 rounded-lg transition-colors">
                                    Trial +
                                </button>

                                {{-- Reset Data --}}
                                <button type="button"
                                        @click="$dispatch('open-reset', { id: {{ $biz->id }}, name: '{{ addslashes($biz->name) }}' })"
                                        class="px-2 py-1 text-xs bg-orange-900/40 hover:bg-orange-800/60 text-orange-400 hover:text-orange-300 rounded-lg transition-colors font-medium flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Reset
                                </button>

                                {{-- Delete --}}
                                <form method="POST" action="{{ route('super-admin.businesses.destroy', $biz) }}"
                                      onsubmit="return confirm('PERMANENTLY delete {{ addslashes($biz->name) }} and ALL its data?')">
                                    @csrf @method('DELETE')
                                    <button class="px-2 py-1 text-xs bg-red-900/40 hover:bg-red-800 text-red-400 rounded-lg transition-colors">
                                        Delete
                                    </button>
                                </form>
                            </div>

                            {{-- Extend Trial panel --}}
                            <div x-show="extendOpen" x-cloak class="mt-2">
                                <form method="POST" action="{{ route('super-admin.businesses.extend-trial', $biz) }}"
                                      class="flex gap-2">
                                    @csrf
                                    <input type="number" name="days" value="30" min="1" max="365"
                                           class="w-16 bg-gray-800 border border-gray-700 rounded-lg px-2 py-1 text-xs text-white focus:outline-none focus:border-yellow-500">
                                    <button class="px-2 py-1 text-xs bg-yellow-700 hover:bg-yellow-600 text-white rounded-lg">days</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-10 text-center text-gray-600">No businesses found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($businesses->hasPages())
        <div class="px-5 py-4 border-t border-gray-800">
            {{ $businesses->links() }}
        </div>
        @endif
    </div>
</div>

<script>
function resetModal() {
    return {
        open: false,
        bizId: null,
        bizName: '',
        formAction: '',
        init() {
            window.addEventListener('open-reset', (e) => {
                this.bizId      = e.detail.id;
                this.bizName    = e.detail.name;
                this.formAction = '/super-admin/businesses/' + e.detail.id + '/reset-data';
                this.open       = true;
            });
        }
    }
}
</script>

@endsection
