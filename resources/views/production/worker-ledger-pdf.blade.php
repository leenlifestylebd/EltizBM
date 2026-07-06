<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    /* DomPDF: no flexbox — everything is table-based. Currency uses "Tk" so no
       glyph breaks in the bundled DejaVu font. */
    @page { margin: 22px 24px; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }

    .header { background: #0f2f4c; color: #fff; border-radius: 8px; padding: 12px 16px; margin-bottom: 14px; }
    .header table { width: 100%; }
    .header h1 { font-size: 16px; font-weight: bold; letter-spacing: 0.3px; }
    .header .sub { font-size: 9.5px; color: #b9d0e2; margin-top: 3px; }
    .header .right { text-align: right; font-size: 8.5px; color: #9db9cd; vertical-align: bottom; }

    .worker { width: 100%; margin-bottom: 12px; }
    .worker .avatar { width: 46px; }
    .worker .avatar span { display: inline-block; width: 40px; height: 40px; line-height: 40px; text-align: center;
        background: #1d5f92; color: #fff; border-radius: 50%; font-size: 18px; font-weight: bold; }
    .worker .name { font-size: 14px; font-weight: bold; color: #111827; }
    .worker .meta { font-size: 9px; color: #6b7280; margin-top: 2px; }

    .cards { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 4px; }
    .cards td { width: 25%; border-radius: 7px; padding: 9px 11px; vertical-align: top; }
    .cards .label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.4px; font-weight: bold; }
    .cards .value { font-size: 14px; font-weight: bold; margin-top: 4px; }
    .cards .sub { font-size: 7.5px; color: #9aa5b1; margin-top: 3px; }
    .c-blue   { background: #eaf3fb; border: 1px solid #c4dcef; } .c-blue   .label, .c-blue   .value { color: #1d5f92; }
    .c-green  { background: #eafaf0; border: 1px solid #bfe8cf; } .c-green  .label, .c-green  .value { color: #1f7a44; }
    .c-red    { background: #fdeeee; border: 1px solid #f4c9c9; } .c-red    .label, .c-red    .value { color: #b3382f; }
    .c-purple { background: #f4eefb; border: 1px solid #ddc9f0; } .c-purple .label, .c-purple .value { color: #6b3fa0; }
    .c-gray   { background: #f4f6f8; border: 1px solid #dfe4ea; } .c-gray   .label { color: #55606d; } .c-gray .value { color: #1f2937; }

    .banner { border-radius: 6px; padding: 7px 11px; font-size: 9px; font-weight: bold; margin-bottom: 12px; }
    .banner.overpay { background: #eafaf0; border: 1px solid #a9dfbf; color: #1f7a44; }
    .banner.due { background: #fdeeee; border: 1px solid #f0b4b4; color: #b3382f; }

    .section { margin-bottom: 14px; }
    .section-title { font-size: 10.5px; font-weight: bold; color: #1f2937; border-bottom: 2px solid #e5e9ee; padding-bottom: 4px; margin-bottom: 7px; }

    table.grid { width: 100%; border-collapse: collapse; font-size: 9px; }
    table.grid thead tr { background: #26374b; color: #fff; }
    table.grid thead th { padding: 5px 8px; text-align: left; font-weight: bold; font-size: 8.5px; }
    table.grid th.r, table.grid td.r { text-align: right; }
    table.grid th.c, table.grid td.c { text-align: center; }
    table.grid tbody td { padding: 5px 8px; border-bottom: 1px solid #eef1f4; }
    table.grid tbody tr:nth-child(even) { background: #f7f9fb; }
    table.grid tfoot td { font-weight: bold; background: #eef2f6; padding: 5px 8px; border-top: 2px solid #cbd5e0; }
    table.grid tfoot td.r { text-align: right; }
    .muted { color: #9aa5b1; }

    .cols { width: 100%; border-collapse: collapse; }
    .cols > tbody > tr > td { vertical-align: top; }
    .cols .left  { width: 50%; padding-right: 7px; }
    .cols .right { width: 50%; padding-left: 7px; }

    .box { border: 1px solid #e5e9ee; border-radius: 7px; padding: 4px 10px; }
    .row { width: 100%; border-collapse: collapse; font-size: 9px; }
    .row td { padding: 4px 0; border-bottom: 1px dotted #e5e9ee; }
    .row tr:last-child td { border-bottom: none; }
    .row td.r { text-align: right; font-weight: bold; }
    .row .date { color: #6b7280; }
    .row .method { font-size: 7.5px; color: #9aa5b1; }
    .row .note { font-size: 7.5px; color: #9aa5b1; }
    .total-row td { font-weight: bold; border-top: 1.5px solid #d5dbe1; padding-top: 6px; }

    .balance td { padding: 4px 0; border-bottom: 1px dotted #e5e9ee; font-size: 9px; }
    .balance tr:last-child td { border-bottom: none; }
    .balance td.r { text-align: right; }
    .balance .grand td { font-weight: bold; font-size: 11px; border-top: 1.5px solid #cbd5e0; padding-top: 6px; }

    .footer { width: 100%; margin-top: 12px; padding-top: 7px; border-top: 1px solid #e5e9ee; font-size: 8px; color: #9aa5b1; }
    .footer td.r { text-align: right; }
</style>
</head>
<body>

@php
    $money = fn ($n) => 'Tk ' . number_format((float) $n);
    $monthLabel = \Carbon\Carbon::parse($month.'-01')->format('F Y');
    $monthShort = \Carbon\Carbon::parse($month.'-01')->format('M Y');
    $monthBalance = $monthEarned - $monthPaid;
@endphp

{{-- Header --}}
<div class="header">
    <table>
        <tr>
            <td>
                <h1>Worker Ledger</h1>
                <div class="sub">{{ $biz->name ?? 'Business' }} &mdash; {{ $monthLabel }}</div>
            </td>
            <td class="right">
                Generated {{ now()->format('d M Y, h:i A') }}<br>
                Piece-bill &middot; Paid &middot; Due
            </td>
        </tr>
    </table>
</div>

{{-- Worker --}}
<table class="worker">
    <tr>
        <td class="avatar"><span>{{ strtoupper(substr($employee->name, 0, 1)) }}</span></td>
        <td>
            <div class="name">{{ $employee->name }}</div>
            <div class="meta">
                {{ $employee->designation ?? $employee->type ?? 'Worker' }}
                @if($employee->phone) &bull; {{ $employee->phone }} @endif
            </div>
        </td>
    </tr>
</table>

{{-- Summary Cards --}}
<table class="cards">
    <tr>
        <td class="c-blue">
            <div class="label">Piece-bill ({{ $monthShort }})</div>
            <div class="value">{{ $money($monthEarned) }}</div>
            <div class="sub">{{ number_format($entries->sum('quantity')) }} pieces</div>
        </td>
        <td class="c-green">
            <div class="label">Paid (this month)</div>
            <div class="value">{{ $money($monthPaid) }}</div>
            <div class="sub">{{ $payments->count() }} payment(s)</div>
        </td>
        <td class="{{ $monthBalance > 0 ? 'c-red' : ($monthBalance < 0 ? 'c-purple' : 'c-gray') }}">
            <div class="label">{{ $monthBalance > 0 ? 'Due (this month)' : ($monthBalance < 0 ? 'Overpay' : 'Status') }}</div>
            <div class="value">{{ $monthBalance == 0 ? 'Clear' : $money(abs($monthBalance)) }}</div>
            <div class="sub">current month only</div>
        </td>
        <td class="{{ $runningDue > 0 ? 'c-red' : ($runningDue < 0 ? 'c-purple' : 'c-gray') }}">
            <div class="label">All-time balance</div>
            <div class="value">{{ $runningDue == 0 ? 'Clear' : $money(abs($runningDue)) }}</div>
            <div class="sub">{{ $runningDue > 0 ? 'due' : ($runningDue < 0 ? 'advance' : 'settled') }}</div>
        </td>
    </tr>
</table>

{{-- Carry-over --}}
@if($carryOver != 0)
<div class="banner {{ $carryOver > 0 ? 'overpay' : 'due' }}">
    @if($carryOver > 0)
        Carry-over from previous months: {{ $money($carryOver) }} advance (overpayment)
    @else
        Carry-over from previous months: {{ $money(abs($carryOver)) }} due (unpaid)
    @endif
</div>
@endif

{{-- Product Summary --}}
<div class="section">
    <div class="section-title">Product Summary &mdash; {{ $monthLabel }}</div>
    <table class="grid">
        <thead>
            <tr>
                <th>Product</th>
                <th>Color</th>
                <th>Size</th>
                <th class="r">Pieces</th>
                <th class="r">Rate</th>
                <th class="r">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productBreakdown as $pb)
            <tr>
                <td>{{ $pb['product_name'] ?? '—' }}</td>
                <td>{{ $pb['color'] ?? '—' }}</td>
                <td>{{ $pb['size'] ?? '—' }}</td>
                <td class="r">{{ number_format($pb['qty'] ?? 0) }}</td>
                <td class="r">{{ $money($pb['rate'] ?? 0) }}</td>
                <td class="r">{{ $money($pb['amount'] ?? 0) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="c muted" style="padding:10px;">No entries</td></tr>
            @endforelse
        </tbody>
        @if($productBreakdown->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td class="r">{{ number_format($productBreakdown->sum('qty')) }}</td>
                <td></td>
                <td class="r">{{ $money($productBreakdown->sum('amount')) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

{{-- Daily Production Entries: full width so it flows across pages (no big gap) --}}
<div class="section">
    <div class="section-title">Daily Production Entries</div>
    <table class="grid">
        <thead>
            <tr>
                <th>Date</th>
                <th>Product</th>
                <th>Color / Size</th>
                <th class="r">Pcs</th>
                <th class="r">Rate</th>
                <th class="r">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $e)
            <tr>
                <td>{{ \Carbon\Carbon::parse($e->entry_date)->format('d M Y') }}</td>
                <td>{{ optional($e->productionRate)->product_name ?? '—' }}</td>
                <td class="muted">
                    {{ optional($e->productionRate)->color ?? '—' }}@if(optional($e->productionRate)->size) / {{ $e->productionRate->size }}@endif
                </td>
                <td class="r">{{ number_format($e->quantity) }}</td>
                <td class="r">{{ $e->productionRate ? $money($e->productionRate->rate) : '—' }}</td>
                <td class="r">{{ $money($e->amount) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="c muted" style="padding:8px;">No entries this month</td></tr>
            @endforelse
        </tbody>
        @if($entries->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td class="r">{{ number_format($entries->sum('quantity')) }}</td>
                <td></td>
                <td class="r">{{ $money($entries->sum('amount')) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

{{-- Payments + Balance: short, side by side at the end --}}
<table class="cols">
    <tr>
        <td class="left">
            <div class="section">
                <div class="section-title">Payments Received</div>
                <div class="box">
                    <table class="row">
                        @forelse($payments as $pay)
                        <tr>
                            <td>
                                <span class="date">{{ \Carbon\Carbon::parse($pay->payment_date)->format('d M Y') }}</span>
                                <span class="method">&bull; {{ ucfirst(str_replace('_',' ', $pay->payment_method ?? 'cash')) }}</span>
                                @if($pay->notes)<br><span class="note">{{ $pay->notes }}</span>@endif
                            </td>
                            <td class="r" style="color:#1f7a44;">{{ $money($pay->amount) }}</td>
                        </tr>
                        @empty
                        <tr><td class="muted" style="padding:8px 0;">No payments this month</td></tr>
                        @endforelse
                        @if($payments->isNotEmpty())
                        <tr class="total-row">
                            <td>Total Paid</td>
                            <td class="r" style="color:#1f7a44;">{{ $money($payments->sum('amount')) }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </td>
        <td class="right">
            <div class="section">
                <div class="section-title">Balance Summary</div>
                <div class="box">
                    <table class="balance">
                        @if($carryOver != 0)
                        <tr>
                            <td>Carry-over {{ $carryOver > 0 ? '(advance)' : '(due)' }}</td>
                            <td class="r" style="{{ $carryOver > 0 ? 'color:#1f7a44;' : 'color:#b3382f;' }}">
                                {{ $carryOver > 0 ? '+' : '-' }}{{ $money(abs($carryOver)) }}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td>Piece-bill ({{ $monthShort }})</td>
                            <td class="r" style="color:#1d5f92;">{{ $money($monthEarned) }}</td>
                        </tr>
                        <tr>
                            <td>Paid ({{ $monthShort }})</td>
                            <td class="r" style="color:#1f7a44;">- {{ $money($monthPaid) }}</td>
                        </tr>
                        <tr class="grand">
                            <td>All-time Balance</td>
                            <td class="r" style="{{ $runningDue > 0 ? 'color:#b3382f;' : ($runningDue < 0 ? 'color:#6b3fa0;' : 'color:#1f7a44;') }}">
                                @if($runningDue == 0) Clear
                                @elseif($runningDue > 0) {{ $money($runningDue) }} Due
                                @else {{ $money(abs($runningDue)) }} Advance
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </td>
    </tr>
</table>

{{-- Footer --}}
<table class="footer">
    <tr>
        <td>{{ $biz->name ?? '' }} &mdash; Worker Ledger &mdash; {{ $employee->name }}</td>
        <td class="r">{{ now()->format('d/m/Y H:i') }}</td>
    </tr>
</table>

</body>
</html>
