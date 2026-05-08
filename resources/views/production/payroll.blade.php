@extends('layouts.app')
@section('title', 'Payroll')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Payroll</h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Monthly salary & payment management</p>
    </div>
    <button onclick="document.getElementById('paymentModal').classList.remove('hidden')"
            class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">
        💰 Add Payment
    </button>
</div>

{{-- Month Filter --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
    <form method="GET" class="flex items-center gap-3">
        <label class="text-xs font-medium text-gray-700 dark:text-slate-300">Month:</label>
        <input type="month" name="month" value="{{ $month }}"
               class="px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">View</button>
    </form>
</div>

{{-- Payroll Table --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-700/50 border-b border-gray-100 dark:border-slate-700">
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Employee</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Type</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Earning</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Advance</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Bonus</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Deduct</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Paid</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Net Payable</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($employees as $emp)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $emp->name }}</p>
                        <p class="text-xs text-gray-400">{{ $emp->designation ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $emp->type === 'production' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                            {{ ucfirst($emp->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">৳{{ number_format($emp->total_earning) }}</td>
                    <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ $emp->total_advance > 0 ? '৳'.number_format($emp->total_advance) : '—' }}</td>
                    <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ $emp->total_bonus > 0 ? '৳'.number_format($emp->total_bonus) : '—' }}</td>
                    <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ $emp->total_deduct > 0 ? '৳'.number_format($emp->total_deduct) : '—' }}</td>
                    <td class="px-4 py-3 text-right text-gray-500">{{ $emp->total_paid > 0 ? '৳'.number_format($emp->total_paid) : '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <span class="font-bold text-lg {{ $emp->net_payable > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                            ৳{{ number_format($emp->net_payable) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="setEmployee({{ $emp->id }}, '{{ $emp->name }}')"
                                class="px-3 py-1.5 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition-colors">
                            Pay
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-4 py-12 text-center text-sm text-gray-400">No employees found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Payment Modal --}}
<div id="paymentModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Add Payment / Advance</h3>
            <button onclick="document.getElementById('paymentModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl">×</button>
        </div>
        <form method="POST" action="{{ route('production.payment.store') }}" class="p-5 space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Employee <span class="text-red-500">*</span></label>
                <select name="employee_id" id="paymentEmployee" required
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Employee</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Type <span class="text-red-500">*</span></label>
                    <select name="type" required class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="advance">Advance</option>
                        <option value="salary">Salary</option>
                        <option value="bonus">Bonus</option>
                        <option value="deduction">Deduction</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Amount <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" required min="1" step="0.01"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="0.00">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="payment_date" value="{{ today()->toDateString() }}" required
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Method</label>
                    <select name="payment_method" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="cash">Cash</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="bank">Bank</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Notes</label>
                <input type="text" name="notes"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Optional...">
            </div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                Save Payment
            </button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function setEmployee(id, name) {
    document.getElementById('paymentEmployee').value = id;
    document.getElementById('paymentModal').classList.remove('hidden');
}
</script>
@endpush