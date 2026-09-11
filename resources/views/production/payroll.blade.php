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

@if(session('success'))
<div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-400">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-400">
    {{ session('error') }}
</div>
@endif

{{-- Month Filter --}}
<div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 p-3 mb-4">
    <form method="GET" class="flex items-center gap-3">
        <label class="text-xs font-medium text-gray-700 dark:text-slate-300">Month:</label>
        <input type="month" name="month" value="{{ $month }}"
               class="px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        <button type="submit" class="px-3 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">View</button>
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
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500" title="Balance carried from before this month">Opening</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Earning</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Advance</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Bonus</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Deduct</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Paid</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">Net Payable</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Status</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
                @forelse($employees as $emp)
                @php $period = $payrollPeriods[$emp->id] ?? null; $locked = $period?->is_finalized; @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors {{ $locked ? 'bg-green-50/40 dark:bg-green-900/5' : '' }}">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $emp->name }}</p>
                        <p class="text-xs text-gray-400">{{ $emp->designation ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $emp->type === 'production' ? 'bg-primary-100 text-primary-700' : 'bg-purple-100 text-purple-700' }}">
                            {{ ucfirst($emp->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right text-xs">
                        @if(abs($emp->opening_balance ?? 0) >= 1)
                            <span class="{{ $emp->opening_balance > 0 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}"
                                  title="{{ $emp->opening_balance > 0 ? 'Due carried from before' : 'Advance carried from before' }}">
                                {{ $emp->opening_balance < 0 ? '−' : '' }}৳{{ number_format(abs($emp->opening_balance)) }}
                            </span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                        ৳{{ number_format($emp->total_earning) }}
                        @if(($emp->total_base ?? 0) > 0 && ($emp->total_production ?? 0) > 0)
                            <span class="block text-xs font-normal text-gray-400">৳{{ number_format($emp->total_base) }} + ৳{{ number_format($emp->total_production) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ $emp->total_advance > 0 ? '৳'.number_format($emp->total_advance) : '—' }}</td>
                    <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ $emp->total_bonus > 0 ? '৳'.number_format($emp->total_bonus) : '—' }}</td>
                    <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ $emp->total_deduct > 0 ? '৳'.number_format($emp->total_deduct) : '—' }}</td>
                    <td class="px-4 py-3 text-right text-gray-500">{{ $emp->total_paid > 0 ? '৳'.number_format($emp->total_paid) : '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        @if($emp->net_payable < -0.5)
                            {{-- Worker has been paid/advanced more than earned — carries forward --}}
                            <span class="font-bold text-lg text-amber-600 dark:text-amber-400" title="Overpaid / advance — carries to next month">
                                −৳{{ number_format(abs($emp->net_payable)) }}
                            </span>
                            <span class="block text-xs text-amber-500">advance</span>
                        @else
                            <span class="font-bold text-lg {{ $emp->net_payable > 0.5 ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                                ৳{{ number_format($emp->net_payable) }}
                            </span>
                            @if($emp->net_payable > 0.5)<span class="block text-xs text-green-500">due</span>@endif
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($locked)
                            <div class="flex flex-col items-center gap-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-xs font-medium rounded-full">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                    Finalized
                                </span>
                                <span class="text-xs text-gray-400">{{ $period->finalized_at?->format('d M') }}</span>
                                <span class="text-xs text-gray-500">Snapshot balance: ৳{{ number_format($period->net_due) }}</span>
                            </div>
                        @else
                            <span class="inline-flex px-2 py-0.5 bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 text-xs font-medium rounded-full">Open</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            @if(!$locked)
                            <button onclick="setEmployee({{ $emp->id }}, '{{ $emp->name }}')"
                                    class="px-2.5 py-1.5 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition-colors">
                                Pay
                            </button>
                            <button onclick="openFinalize({{ $emp->id }}, '{{ addslashes($emp->name) }}', {{ $emp->total_earning }}, {{ $emp->type === 'production' ? $emp->productionEntries->sum('quantity') : 0 }}, {{ $emp->total_paid }}, {{ $emp->net_payable }})"
                                    class="px-2.5 py-1.5 bg-primary-600 text-white text-xs rounded-lg hover:bg-primary-700 transition-colors">
                                Finalize
                            </button>
                            @else
                            <form method="POST" action="{{ route('production.payroll.unfinalize', $period->id) }}"
                                  onsubmit="return confirm('Reopen this payroll snapshot so it can be finalized again?')">
                                @csrf
                                <button type="submit" class="px-2.5 py-1.5 bg-gray-500 text-white text-xs rounded-lg hover:bg-gray-600 transition-colors">
                                    Unlock
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="11" class="px-4 py-12 text-center text-sm text-gray-400">No employees found</td></tr>
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
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Employee</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Type <span class="text-red-500">*</span></label>
                    <select name="type" required class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="advance">Advance</option>
                        <option value="salary">Salary</option>
                        <option value="bonus">Bonus</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Amount <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" required min="1" step="0.01"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                           placeholder="0.00">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="payment_date" value="{{ today()->toDateString() }}" required
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Method</label>
                    <select name="payment_method" class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
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
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                       placeholder="Optional...">
            </div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                Save Payment
            </button>
        </form>
    </div>
</div>

{{-- Finalize Payroll Modal --}}
<div id="finalizeModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md shadow-2xl">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Finalize Payroll</h3>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Lock this month's payroll record</p>
            </div>
            <button onclick="document.getElementById('finalizeModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
        </div>
        <form id="finalizeForm" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">

            {{-- Summary --}}
            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-xl p-4 space-y-2">
                <p class="text-xs font-medium text-gray-500 dark:text-slate-400 mb-3">Period: <span class="text-gray-800 dark:text-white">{{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}</span></p>
                <div class="flex justify-between text-xs">
                    <span class="text-gray-500 dark:text-slate-400">Gross Earning</span>
                    <span id="finalGross" class="font-semibold text-gray-800 dark:text-white"></span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-gray-500 dark:text-slate-400">Total Paid</span>
                    <span id="finalPaid" class="font-semibold text-gray-800 dark:text-white"></span>
                </div>
                <div class="flex justify-between text-xs border-t border-gray-200 dark:border-slate-600 pt-2 mt-2">
                    <span class="text-gray-700 dark:text-slate-200 font-medium">Net Due</span>
                    <span id="finalDue" class="font-bold text-green-600 dark:text-green-400"></span>
                </div>
            </div>

            <input type="hidden" name="gross_earning" id="inputGross">
            <input type="hidden" name="total_pieces"  id="inputPieces">
            <input type="hidden" name="total_paid"    id="inputPaid">
            <input type="hidden" name="net_due"       id="inputDue">

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">Notes (optional)</label>
                <textarea name="notes" rows="2"
                          class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                          placeholder="Any remarks about this payroll..."></textarea>
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                <p class="text-xs text-yellow-700 dark:text-yellow-400">
                    Finalizing saves a snapshot of this employee's payroll for <strong>{{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}</strong>.
                    Further payments update the live balance. The saved snapshot keeps the amounts recorded at finalization.
                </p>
            </div>

            <button type="submit"
                    class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 text-sm rounded-lg transition-colors">
                🔒 Confirm & Finalize
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

function openFinalize(empId, name, gross, pieces, paid, netDue) {
    document.getElementById('finalizeForm').action = '/production/payroll/' + empId + '/finalize';
    document.getElementById('finalGross').textContent = '৳' + gross.toLocaleString('en-BD', {minimumFractionDigits: 0});
    document.getElementById('finalPaid').textContent  = '৳' + paid.toLocaleString('en-BD', {minimumFractionDigits: 0});
    document.getElementById('finalDue').textContent   = '৳' + netDue.toLocaleString('en-BD', {minimumFractionDigits: 0});
    document.getElementById('inputGross').value  = gross;
    document.getElementById('inputPieces').value = pieces;
    document.getElementById('inputPaid').value   = paid;
    document.getElementById('inputDue').value    = netDue;
    document.getElementById('finalizeModal').classList.remove('hidden');
}
</script>
@endpush