@extends('layouts.app')
@section('title', 'Employees')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Employees</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ $employees->total() }} team members</p>
    </div>
    <a href="{{ route('employees.create') }}"
       class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span class="hidden xs:inline">Add Employee</span>
        <span class="xs:hidden">Add</span>
    </a>
</div>

{{-- Stats --}}
<div class="grid grid-cols-3 gap-3 mb-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-gray-100 dark:border-slate-700 text-center">
        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Total</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-green-100 dark:border-green-900/30 text-center">
        <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $stats['active'] }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Active</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-primary-100 dark:border-primary-900/30 text-center">
        <p class="text-xl font-bold text-primary-600 dark:text-primary-400">{{ $stats['present_today'] }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Present Today</p>
    </div>
</div>

{{-- Search --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
    <form method="GET" class="flex gap-2">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, phone..."
                   class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-transparent text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <select name="status" class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">All</option>
            <option value="active"   {{ request('status')==='active'   ? 'selected':'' }}>Active</option>
            <option value="inactive" {{ request('status')==='inactive' ? 'selected':'' }}>Inactive</option>
        </select>
        <button type="submit" class="px-3 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm transition-colors">Go</button>
    </form>
</div>

{{-- Employee Cards - Mobile friendly --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
    @forelse($employees as $emp)
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 hover:shadow-sm transition-all group">
        <div class="flex items-start gap-3 mb-3">
            {{-- Avatar --}}
            <div class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0 text-white font-bold text-sm
                {{ ['bg-primary-500','bg-purple-500','bg-green-500','bg-orange-500','bg-pink-500','bg-teal-500'][crc32($emp->name) % 6] }}">
                {{ strtoupper(substr($emp->name, 0, 2)) }}
            </div>
            <div class="flex-1 min-w-0">
                <a href="{{ route('employees.show', $emp) }}"
                   class="font-semibold text-sm text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 truncate block">
                    {{ $emp->name }}
                </a>
                <p class="text-xs text-gray-500 dark:text-slate-400 truncate mt-0.5">{{ $emp->designation ?? 'Employee' }}</p>
                @if($emp->department)
                    <span class="inline-block text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 px-1.5 py-0.5 rounded mt-1">{{ $emp->department }}</span>
                @endif
            </div>
            <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                {{ $emp->status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-slate-400' }}">
                {{ ucfirst($emp->status) }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-2 mb-3">
            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2 text-center">
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $emp->today_production }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500">Today Qty</p>
            </div>
            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-2 text-center">
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $emp->month_production }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500">Month Qty</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-2 text-center">
                <p class="text-sm font-bold text-green-600 dark:text-green-400">৳{{ number_format($emp->month_earning) }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500">Earning</p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-2 text-center">
                <p class="text-sm font-bold text-red-600 dark:text-red-400">৳{{ number_format($emp->month_advance) }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500">Advance</p>
            </div>
        </div>
        <div class="flex items-center justify-between px-2 py-1.5 rounded-lg mb-2
            {{ $emp->month_net >= 0 ? 'bg-purple-50 dark:bg-purple-900/20' : 'bg-orange-50 dark:bg-orange-900/20' }}">
            <span class="text-xs text-gray-500 dark:text-slate-400">Net Payable</span>
            <span class="text-sm font-bold {{ $emp->month_net >= 0 ? 'text-purple-600 dark:text-purple-400' : 'text-orange-600' }}">
                ৳{{ number_format(abs($emp->month_net)) }}{{ $emp->month_net < 0 ? ' (Overpaid)' : '' }}
            </span>
        </div>

        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-slate-400 mb-3">
            @if($emp->phone)
                <a href="tel:{{ $emp->phone }}" class="flex items-center gap-1 hover:text-primary-600 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ $emp->phone }}
                </a>
            @endif
            <span class="font-medium text-gray-700 dark:text-slate-300">৳{{ number_format($emp->salary) }}/mo</span>
        </div>

        <div class="flex items-center gap-1.5 pt-2 border-t border-gray-50 dark:border-slate-700 opacity-0 group-hover:opacity-100 transition-opacity">
            <a href="{{ route('employees.show', $emp) }}"
               class="flex-1 py-1.5 text-center text-xs font-medium text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 transition-colors">
                View
            </a>
            <a href="{{ route('production.tasks.index') }}?employee_id={{ $emp->id }}"
               class="flex-1 py-1.5 text-center text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                Tasks
            </a>
            <a href="{{ route('employees.attendance', $emp) }}"
               class="flex-1 py-1.5 text-center text-xs font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                Attendance
            </a>
            <button type="button"
                    @click="$dispatch('open-login', { id: {{ $emp->id }}, name: @js($emp->name), has: {{ $emp->user_id ? 'true' : 'false' }}, phone: @js($emp->phone) })"
                    title="{{ $emp->user_id ? 'Reset login PIN' : 'Enable login' }}"
                    class="px-2.5 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $emp->user_id ? 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100' : 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100' }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </button>
            <a href="{{ route('employees.edit', $emp) }}"
               class="px-2.5 py-1.5 text-xs font-medium text-gray-500 dark:text-slate-400 bg-gray-100 dark:bg-slate-700 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </a>
        </div>
    </div>
    @empty
    <div class="col-span-full bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 py-16 text-center">
        <svg class="w-12 h-12 text-gray-200 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <p class="text-sm text-gray-400 dark:text-slate-500">No employees yet</p>
        <a href="{{ route('employees.create') }}" class="mt-2 inline-block text-xs text-primary-600 hover:underline">Add your first employee</a>
    </div>
    @endforelse
</div>

@if($employees->hasPages())
<div class="mt-4">{{ $employees->withQueryString()->links() }}</div>
@endif

{{-- Enable / Reset worker login (phone + PIN) --}}
<div x-data="{ open: false, emp: { id: null, name: '', has: false, phone: '' } }"
     @open-login.window="emp = $event.detail; open = true">
    <div x-show="open" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-4">
        <div @click.outside="open = false" class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-sm">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    <span x-text="emp.has ? 'Reset PIN' : 'Enable login'"></span> — <span x-text="emp.name"></span>
                </h3>
                <button @click="open = false" class="p-1 text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            </div>
            <form method="POST" :action="`{{ url('employees') }}/${emp.id}/enable-login`" class="p-5 space-y-4">
                @csrf
                <div class="text-xs text-gray-500 dark:text-slate-400">
                    Worker logs in at <span class="font-medium text-gray-700 dark:text-slate-200">/worker-login</span>
                    with phone <span class="font-medium" x-text="emp.phone || '(no phone set — add one first)'"></span> and this PIN.
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Set a 4–6 digit PIN</label>
                    <input type="text" name="pin" inputmode="numeric" maxlength="6" required pattern="\d{4,6}"
                           placeholder="e.g. 1234"
                           class="w-full px-4 py-2.5 text-lg text-center tracking-[0.3em] bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl text-sm transition-colors">
                    <span x-text="emp.has ? 'Update PIN' : 'Enable login'"></span>
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
