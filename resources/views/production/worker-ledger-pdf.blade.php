<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1a202c; background: #fff; }

    .header { background: #1a3a5c; color: #fff; padding: 14px 20px; margin-bottom: 14px; }
    .header h1 { font-size: 15px; font-weight: bold; margin-bottom: 2px; }
    .header p { font-size: 9.5px; opacity: 0.8; }
    .header .meta { display: flex; justify-content: space-between; align-items: flex-end; }
    .header .right { text-align: right; font-size: 9px; opacity: 0.75; }

    .worker-info { margin: 0 20px 12px; display: flex; align-items: center; gap: 12px; }
    .worker-avatar { width: 40px; height: 40px; background: #ebf8ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: bold; color: #2b6cb0; border: 2px solid #bee3f8; }
    .worker-name { font-size: 14px; font-weight: bold; color: #2d3748; }
    .worker-meta { font-size: 9px; color: #718096; margin-top: 2px; }

    .summary-row { display: flex; gap: 10px; margin: 0 20px 12px; }
    .summary-card { flex: 1; border-radius: 6px; padding: 8px 12px; }
    .summary-card.blue { background: #ebf8ff; border: 1px solid #bee3f8; }
    .summary-card.green { background: #f0fff4; border: 1px solid #c6f6d5; }
    .summary-card.red { background: #fff5f5; border: 1px solid #fed7d7; }
    .summary-card.purple { background: #faf5ff; border: 1px solid #e9d8fd; }
    .summary-card.gray { background: #f7fafc; border: 1px solid #e2e8f0; }
    .summary-card .label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; font-weight: 600; }
    .summary-card.blue .label { color: #2b6cb0; }
    .summary-card.green .label { color: #276749; }
    .summary-card.red .label { color: #c53030; }
    .summary-card.purple .label { color: #553c9a; }
    .summary-card.gray .label { color: #4a5568; }
    .summary-card .value { font-size: 13px; font-weight: bold; }
    .summary-card.blue .value { color: #2b6cb0; }
    .summary-card.green .value { color: #276749; }
    .summary-card.red .value { color: #c53030; }
    .summary-card.purple .value { color: #553c9a; }
    .summary-card.gray .value { color: #2d3748; }
    .summary-card .sub { font-size: 8px; color: #a0aec0; margin-top: 2px; }

    .carry-banner { margin: 0 20px 10px; padding: 6px 10px; border-radius: 5px; font-size: 9px; font-weight: 600; }
    .carry-banner.overpay { background: #f0fff4; border: 1px solid #9ae6b4; color: #276749; }
    .carry-banner.due { background: #fff5f5; border: 1px solid #feb2b2; color: #c53030; }

    .section { margin: 0 20px 14px; }
    .section-title { font-size: 11px; font-weight: bold; color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 7px; }

    table { width: 100%; border-collapse: collapse; font-size: 9px; }
    thead tr { background: #2d3748; color: #fff; }
    thead th { padding: 5px 7px; text-align: left; font-weight: 600; font-size: 8.5px; }
    thead th.right { text-align: right; }
    thead th.center { text-align: center; }
    tbody tr { border-bottom: 1px solid #f0f0f0; }
    tbody tr:nth-child(even) { background: #f7fafc; }
    tbody td { padding: 4.5px 7px; }
    tbody td.right { text-align: right; }
    tbody td.center { text-align: center; }
    tfoot tr td { font-weight: bold; background: #edf2f7; padding: 5px 7px; border-top: 2px solid #cbd5e0; }
    tfoot tr td.right { text-align: right; }

    .two-col { display: flex; gap: 14px; margin: 0 20px 14px; }
    .two-col .left { flex: 1.4; }
    .two-col .right { flex: 1; }
    .two-col .section { margin: 0 0 10px; }

    .balance-box { border: 1.5px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; font-size: 9px; }
    .balance-row { display: flex; justify-content: space-between; padding: 3px 0; border-bottom: 1px dotted #e2e8f0; }
    .balance-row:last-child { border-bottom: none; }
    .balance-row.total { font-weight: bold; font-size: 10px; padding-top: 5px; margin-top: 2px; }
    .balance-row.total.due { color: #c53030; }
    .balance-row.total.overpay { color: #553c9a; }
    .balance-row.total.clear { color: #276749; }

    .payment-item { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px dotted #e2e8f0; font-size: 9px; }
    .payment-item:last-child { border-bottom: none; }
    .payment-date { color: #718096; }
    .payment-amount { font-weight: bold; color: #276749; }
    .payment-method { font-size: 8px; color: #a0aec0; }

    .footer { margin: 10px 20px 0; padding-top: 7px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 8px; color: #718096; }
    .page-break { page-break-after: always; }
</style>
</head>
<body>

<div class="header">
    <div class="meta">
        <div>
            <h1>Worker Ledger</h1>
            <p>{{ $biz->name ?? 'Business' }} &mdash; {{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}</p>
        </div>
        <div class="right">
            Generated: {{ now()->format('d M Y, h:i A') }}<br>
            P.Bill vs Paid vs Due
        </div>
    </div>
</div>

{{-- Worker Info --}}
<div class="worker-info">
    <div class="worker-avatar">{{ strtoupper(substr($employee->name, 0, 1)) }}</div>
    <div>
        <div class="worker-name">{{ $employee->name }}</div>
        <div class="worker-meta">
            {{ $employee->designation ?? $employee->type ?? 'Worker' }}
            @if($employee->phone) &bull; {{ $employee->phone }} @endif
        </div>
    </div>
</div>

{{-- Summary Cards --}}
<div class="summary-row">
    <div class="summary-card blue">
        <div class="label">P.Bill (This Month)</div>
        <div class="value">৳{{ number_format($monthEarned) }}</div>
        <div class="sub">{{ $entries->sum('quantity') }} pieces</div>
    </div>
    <div class="summary-card green">
        <div class="label">Paid (This Month)</div>
        <div class="value">৳{{ number_format($monthPaid) }}</div>
        <div class="sub">{{ $payments->count() }} payment(s)</div>
    </div>
    @php
        $monthBalance = $monthEarned - $monthPaid;
    @endphp
    <div class="summary-card {{ $monthBalance > 0 ? 'red' : ($monthBalance < 0 ? 'purple' : 'gray') }}">
        <div class="label">{{ $monthBalance > 0 ? 'Due (This Month)' : ($monthBalance < 0 ? 'Overpay' : 'Status') }}</div>
        <div class="value">{{ $monthBalance == 0 ? 'Clear' : '৳'.number_format(abs($monthBalance)) }}</div>
        <div class="sub">current month only</div>
    </div>
    <div class="summary-card {{ $runningDue > 0 ? 'red' : ($runningDue < 0 ? 'purple' : 'gray') }}">
        <div class="label">All-time Balance</div>
        <div class="value">{{ $runningDue == 0 ? 'Clear' : '৳'.number_format(abs($runningDue)) }}</div>
        <div class="sub">{{ $runningDue > 0 ? 'due' : ($runningDue < 0 ? 'advance' : '') }}</div>
    </div>
</div>

{{-- Carry-over Banner --}}
@if($carryOver != 0)
<div class="carry-banner {{ $carryOver > 0 ? 'overpay' : 'due' }}">
    @if($carryOver > 0)
        Carry-over from previous months: ৳{{ number_format($carryOver) }} advance (overpayment)
    @else
        Carry-over from previous months: ৳{{ number_format(abs($carryOver)) }} due (unpaid)
    @endif
</div>
@endif

{{-- Product Breakdown --}}
<div class="section">
    <div class="section-title">Product Summary — {{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}</div>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Color</th>
                <th>Size</th>
                <th class="right">Pieces</th>
                <th class="right">Rate</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productBreakdown as $pb)
            <tr>
                <td>{{ $pb->product_name ?? '—' }}</td>
                <td>{{ $pb->color ?? '—' }}</td>
                <td>{{ $pb->size ?? '—' }}</td>
                <td class="right">{{ number_format($pb->total_qty) }}</td>
                <td class="right">৳{{ number_format($pb->avg_rate, 2) }}</td>
                <td class="right">৳{{ number_format($pb->total_amount) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="center" style="color:#a0aec0; padding:10px;">No entries</td></tr>
            @endforelse
        </tbody>
        @if($productBreakdown->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td class="right">{{ number_format($productBreakdown->sum('total_qty')) }}</td>
                <td></td>
                <td class="right">৳{{ number_format($productBreakdown->sum('total_amount')) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

{{-- Daily Entries + Payments side by side --}}
<div class="two-col">
    <div class="left">
        <div class="section">
            <div class="section-title">Daily Production Entries</div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Color / Size</th>
                        <th class="right">Pcs</th>
                        <th class="right">Rate</th>
                        <th class="right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $e)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($e->entry_date)->format('d M') }}</td>
                        <td>{{ optional($e->productionRate)->product_name ?? '—' }}</td>
                        <td style="font-size:8px; color:#718096;">
                            {{ optional($e->productionRate)->color ?? '' }}
                            @if(optional($e->productionRate)->size) / {{ $e->productionRate->size }} @endif
                        </td>
                        <td class="right">{{ number_format($e->quantity) }}</td>
                        <td class="right">{{ $e->productionRate ? '৳'.number_format($e->productionRate->rate_per_piece,2) : '—' }}</td>
                        <td class="right">৳{{ number_format($e->amount) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="center" style="color:#a0aec0; padding:8px;">No entries this month</td></tr>
                    @endforelse
                </tbody>
                @if($entries->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="3">Total</td>
                        <td class="right">{{ number_format($entries->sum('quantity')) }}</td>
                        <td></td>
                        <td class="right">৳{{ number_format($entries->sum('amount')) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="right">
        <div class="section">
            <div class="section-title">Payments Received</div>
            @forelse($payments as $pay)
            <div class="payment-item">
                <div>
                    <div class="payment-date">{{ \Carbon\Carbon::parse($pay->payment_date)->format('d M Y') }}</div>
                    <div class="payment-method">{{ ucfirst(str_replace('_',' ',$pay->payment_method ?? 'cash')) }}</div>
                    @if($pay->notes) <div style="font-size:7.5px; color:#a0aec0;">{{ $pay->notes }}</div> @endif
                </div>
                <div class="payment-amount">৳{{ number_format($pay->amount) }}</div>
            </div>
            @empty
            <div style="color:#a0aec0; font-size:9px; padding:8px 0;">No payments this month</div>
            @endforelse

            @if($payments->isNotEmpty())
            <div class="payment-item" style="font-weight:bold; border-top: 1.5px solid #e2e8f0; margin-top:4px; padding-top:5px;">
                <div>Total Paid</div>
                <div style="color:#276749;">৳{{ number_format($payments->sum('amount')) }}</div>
            </div>
            @endif
        </div>

        <div class="section">
            <div class="section-title">Balance Summary</div>
            <div class="balance-box">
                @if($carryOver != 0)
                <div class="balance-row">
                    <span>Carry-over {{ $carryOver > 0 ? '(advance)' : '(due)' }}</span>
                    <span style="{{ $carryOver > 0 ? 'color:#276749;' : 'color:#c53030;' }}">
                        {{ $carryOver > 0 ? '+' : '-' }}৳{{ number_format(abs($carryOver)) }}
                    </span>
                </div>
                @endif
                <div class="balance-row">
                    <span>P.Bill ({{ \Carbon\Carbon::parse($month.'-01')->format('M Y') }})</span>
                    <span style="color:#2b6cb0;">৳{{ number_format($monthEarned) }}</span>
                </div>
                <div class="balance-row">
                    <span>Paid ({{ \Carbon\Carbon::parse($month.'-01')->format('M Y') }})</span>
                    <span style="color:#276749;">- ৳{{ number_format($monthPaid) }}</span>
                </div>
                @php $allTimeBalance = $runningDue; @endphp
                <div class="balance-row total {{ $allTimeBalance > 0 ? 'due' : ($allTimeBalance < 0 ? 'overpay' : 'clear') }}">
                    <span>All-time Balance</span>
                    <span>
                        @if($allTimeBalance == 0) ✓ Clear
                        @elseif($allTimeBalance > 0) ৳{{ number_format($allTimeBalance) }} Due
                        @else ৳{{ number_format(abs($allTimeBalance)) }} Advance
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="footer">
    <span>{{ $biz->name ?? '' }} &mdash; Worker Ledger &mdash; {{ $employee->name }}</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
