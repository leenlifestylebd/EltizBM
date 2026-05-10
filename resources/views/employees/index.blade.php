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
       class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
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
    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-blue-100 dark:border-blue-900/30 text-center">
        <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['present_today'] }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Present Today</p>
    </div>
</div>

{{-- Search --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
    <form method="GET" class="flex gap-2">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, phone..."
                   class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-transparent text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <select name="status" class="text-sm px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All</option>
            <option value="active"   {{ request('status')==='active'   ? 'selected':'' }}>Active</option>
            <option value="inactive" {{ request('status')==='inactive' ? 'selected':'' }}>Inactive</option>
        </select>
        <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm transition-colors">Go</button>
    </form>
</div>

{{-- Employee Cards - Mobile friendly --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
    @forelse($employees as $emp)
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4 hover:shadow-sm transition-all group">
        <div class="flex items-start gap-3 mb-3">
            {{-- Avatar --}}
            <div class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0 text-white font-bold text-sm
                {{ ['bg-blue-500','bg-purple-500','bg-green-500','bg-orange-500','bg-pink-500','bg-teal-500'][crc32($emp->name) % 6] }}">
                {{ strtoupper(substr($emp->name, 0, 2)) }}
            </div>
            <div class="flex-1 min-w-0">
                <a href="{{ route('employees.show', $emp) }}"
                   class="font-semibold text-sm text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 truncate block">
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
                <a href="tel:{{ $emp->phone }}" class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ $emp->phone }}
                </a>
            @endif
            <span class="font-medium text-gray-700 dark:text-slate-300">৳{{ number_format($emp->salary) }}/mo</span>
        </div>

        <div class="flex items-center gap-1.5 pt-2 border-t border-gray-50 dark:border-slate-700 opacity-0 group-hover:opacity-100 transition-opacity">
            <a href="{{ route('employees.show', $emp) }}"
               class="flex-1 py-1.5 text-center text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                View
            </a>
            <a href="{{ route('production.index') }}"
               class="flex-1 py-1.5 text-center text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                Production
            </a>
            <a href="{{ route('employees.attendance', $emp) }}"
               class="flex-1 py-1.5 text-center text-xs font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                Attendance
            </a>
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
        <a href="{{ route('employees.create') }}" class="mt-2 inline-block text-xs text-blue-600 hover:underline">Add your first employee</a>
    </div>
    @endforelse
</div>

@if($employees->hasPages())
<div class="mt-4">{{ $employees->withQueryString()->links() }}</div>
@endif

@endsection
