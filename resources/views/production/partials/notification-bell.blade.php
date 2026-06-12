{{-- In-app notification bell. Expects: $notifications (collection), $notifUnread (int), $employee --}}
<div x-data="{
        open: false,
        unread: {{ (int)($notifUnread ?? 0) }},
        toggle() {
            this.open = !this.open;
            if (this.open && this.unread > 0) {
                fetch('{{ route('production.notifications.read') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ employee_id: {{ $employee->id }} })
                }).then(() => { this.unread = 0; });
            }
        }
    }"
    class="relative">

    <button @click="toggle()" type="button"
            class="relative w-9 h-9 flex items-center justify-center rounded-lg bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 text-gray-500 dark:text-slate-300 hover:text-primary-600 transition-colors">
        <svg class="w-4.5 h-4.5" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span x-show="unread > 0" x-cloak
              class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 flex items-center justify-center text-[10px] font-bold text-white bg-red-500 rounded-full"
              x-text="unread > 9 ? '9+' : unread"></span>
    </button>

    <div x-show="open" x-cloak @click.outside="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="absolute right-0 mt-2 w-72 sm:w-80 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 shadow-lg z-50 overflow-hidden">

        <div class="px-3 py-2.5 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
            <p class="text-xs font-semibold text-gray-900 dark:text-white">🔔 নোটিফিকেশন</p>
            @if(($notifUnread ?? 0) > 0)
                <span class="text-[10px] text-gray-400">{{ $notifUnread }}টি নতুন</span>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-gray-50 dark:divide-slate-700/50">
            @forelse($notifications ?? [] as $n)
            <div class="px-3 py-2.5 {{ is_null($n->read_at) ? 'bg-primary-50/50 dark:bg-primary-900/10' : '' }}">
                <div class="flex items-start gap-2">
                    <span class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-sm
                        {{ $n->type === 'payment' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-blue-100 dark:bg-blue-900/30' }}">
                        {{ $n->type === 'payment' ? '💰' : '📋' }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-900 dark:text-white">{{ $n->title }}</p>
                        @if($n->message)
                            <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5 leading-snug">{{ $n->message }}</p>
                        @endif
                        <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-1">{{ $n->created_at->diffForHumans() }}</p>
                    </div>
                    @if(is_null($n->read_at))
                        <span class="flex-shrink-0 w-1.5 h-1.5 rounded-full bg-primary-500 mt-1.5"></span>
                    @endif
                </div>
            </div>
            @empty
            <div class="px-3 py-8 text-center">
                <p class="text-2xl mb-1">🔕</p>
                <p class="text-xs text-gray-400 dark:text-slate-500">কোনো নোটিফিকেশন নেই</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
