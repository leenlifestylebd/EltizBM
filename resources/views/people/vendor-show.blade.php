@extends('layouts.app')
@section('title', $vendor->name)

@section('content')

<div class="max-w-4xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-3">
            <a href="{{ route('people.vendors') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ $vendor->name }}</h2>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Vendor since {{ $vendor->created_at->format('d M Y') }}</p>
            </div>
        </div>
        <button onclick="document.getElementById('editModal').classList.remove('hidden')"
                class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Info Panel --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Contact Info</h3>
                <div class="space-y-2.5">
                    @if($vendor->phone)
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span class="text-sm text-gray-700 dark:text-slate-200">{{ $vendor->phone }}</span>
                    </div>
                    @endif
                    @if($vendor->email)
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="text-sm text-gray-700 dark:text-slate-200">{{ $vendor->email }}</span>
                    </div>
                    @endif
                    @if($vendor->address)
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="text-sm text-gray-700 dark:text-slate-200">{{ $vendor->address }}</span>
                    </div>
                    @endif
                    @if(!$vendor->phone && !$vendor->email && !$vendor->address)
                        <p class="text-xs text-gray-400 dark:text-slate-500">No contact info added</p>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Summary</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-slate-400">Total Orders</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $purchaseOrders->total() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-slate-400">Total Purchased</span>
                        <span class="text-sm font-semibold text-green-600 dark:text-green-400">৳{{ number_format($totalPurchased) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-slate-400">Due Amount</span>
                        <span class="text-sm font-semibold {{ $vendor->due_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }}">
                            {{ $vendor->due_amount > 0 ? '৳'.number_format($vendor->due_amount) : '৳0' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Purchase Orders --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-50 dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Purchase Orders</h3>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-slate-700">
                    @forelse($purchaseOrders as $po)
                    <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $po->po_number }}</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $po->created_at->format('d M Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">৳{{ number_format($po->total) }}</p>
                            @php
                            $poColors = ['draft'=>'bg-gray-100 text-gray-600','ordered'=>'bg-primary-100 text-primary-700','received'=>'bg-green-100 text-green-700','partial'=>'bg-yellow-100 text-yellow-700','cancelled'=>'bg-red-100 text-red-700'];
                            @endphp
                            <span class="inline-flex mt-0.5 px-2 py-0.5 rounded-full text-xs font-medium {{ $poColors[$po->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($po->status) }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="px-4 py-10 text-center text-sm text-gray-400 dark:text-slate-500">No purchase orders yet</div>
                    @endforelse
                </div>
                @if($purchaseOrders->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $purchaseOrders->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-end sm:items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Edit Vendor</h3>
            <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('people.vendors.update', $vendor) }}" class="p-5 space-y-3">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ $vendor->name }}" required class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Phone</label>
                    <input type="text" name="phone" value="{{ $vendor->phone }}" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ $vendor->email }}" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Address</label>
                <input type="text" name="address" value="{{ $vendor->address }}" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
            <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">Update Vendor</button>
        </form>
    </div>
</div>

@endsection
