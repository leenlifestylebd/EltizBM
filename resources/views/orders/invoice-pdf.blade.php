<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice #{{ $order->order_number }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #1f2937; background: #fff; }

.header { background: {{ $business->themeColor('600') }}; color: #fff; padding: 24px 32px; }
.header-grid { display: table; width: 100%; }
.header-left { display: table-cell; vertical-align: top; }
.header-right { display: table-cell; vertical-align: top; text-align: right; width: 180px; }

.biz-name { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
.biz-sub { font-size: 12px; color: rgba(255,255,255,0.8); margin-top: 2px; }

.inv-label { font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: rgba(255,255,255,0.6); }
.inv-number { font-size: 28px; font-weight: 900; margin: 4px 0; }
.inv-date { font-size: 12px; color: rgba(255,255,255,0.75); }

.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; letter-spacing: 1px; margin-top: 6px; }
.badge-paid    { background: #22c55e; color: #fff; }
.badge-partial { background: #eab308; color: #fff; }
.badge-unpaid  { background: #ef4444; color: #fff; }

.body { padding: 24px 32px; }

.info-grid { display: table; width: 100%; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f3f4f6; }
.info-cell { display: table-cell; vertical-align: top; width: 50%; }

.section-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: #9ca3af; margin-bottom: 8px; }
.customer-name { font-size: 15px; font-weight: 700; color: #111827; }
.customer-sub  { font-size: 12px; color: #6b7280; margin-top: 3px; }

.detail-table { width: 100%; }
.detail-table td { font-size: 12px; padding: 2px 0; }
.detail-table td:first-child { color: #9ca3af; padding-right: 12px; }
.detail-table td:last-child { font-weight: 600; color: #1f2937; text-align: right; }

table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
table.items thead tr { background: #f9fafb; }
table.items th {
    padding: 10px 12px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #6b7280;
    border-bottom: 2px solid #f3f4f6;
}
table.items th:first-child { text-align: left; }
table.items th:last-child  { text-align: right; }
table.items td { padding: 10px 12px; border-bottom: 1px solid #f9fafb; vertical-align: top; }
table.items td.num { text-align: center; font-size: 13px; font-weight: 600; }
table.items td.right { text-align: right; }
table.items td.total { text-align: right; font-weight: 700; font-size: 14px; }
.product-name { font-size: 13px; font-weight: 600; color: #111827; }
.product-sku  { font-size: 10px; color: #9ca3af; font-family: monospace; margin-top: 2px; }

.summary-wrap { text-align: right; margin-bottom: 20px; }
.summary-table { display: inline-table; min-width: 220px; }
.summary-row { display: table-row; }
.summary-row td { display: table-cell; padding: 4px 0; font-size: 13px; color: #4b5563; }
.summary-row td:last-child { text-align: right; padding-left: 20px; }
.summary-total td { font-size: 16px; font-weight: 900; color: #111827; border-top: 2px solid #e5e7eb; padding-top: 8px; }
.summary-paid td { color: #16a34a; font-weight: 600; }
.summary-due  td { color: #dc2626; font-weight: 700; }

.notes { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; }
.notes-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #92400e; margin-bottom: 4px; }
.notes-text { font-size: 12px; color: #78350f; }

.footer { border-top: 1px solid #f3f4f6; padding-top: 16px; display: table; width: 100%; }
.footer-left  { display: table-cell; vertical-align: bottom; }
.footer-right { display: table-cell; vertical-align: bottom; text-align: right; }
.footer-biz   { font-size: 13px; font-weight: 700; color: #374151; }
.footer-sub   { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.footer-thanks { font-size: 12px; color: #9ca3af; }
.footer-gen   { font-size: 10px; color: #d1d5db; margin-top: 2px; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="header-grid">
        <div class="header-left">
            <div class="biz-name">{{ $business->name }}</div>
            @if($business->phone)
            <div class="biz-sub">{{ $business->phone }}</div>
            @endif
            @if($business->email)
            <div class="biz-sub">{{ $business->email }}</div>
            @endif
            @if($business->address)
            <div class="biz-sub" style="margin-top:4px; font-size:11px;">{{ $business->address }}</div>
            @endif
        </div>
        <div class="header-right">
            <div class="inv-label">Invoice</div>
            <div class="inv-number">#{{ $order->order_number }}</div>
            <div class="inv-date">{{ $order->created_at->format('d M Y') }}</div>
            @php
                $badgeClass = match($order->payment_status) {
                    'paid'    => 'badge-paid',
                    'partial' => 'badge-partial',
                    default   => 'badge-unpaid',
                };
                $badgeText = strtoupper($order->payment_status ?? 'UNPAID');
            @endphp
            <span class="badge {{ $badgeClass }}">{{ $badgeText }}</span>
        </div>
    </div>
</div>

<div class="body">

    {{-- Bill To + Details --}}
    <div class="info-grid">
        <div class="info-cell">
            <div class="section-label">Bill To</div>
            <div class="customer-name">{{ $order->customer_name }}</div>
            @if($order->customer_phone)
            <div class="customer-sub">{{ $order->customer_phone }}</div>
            @endif
            @if($order->shipping_address)
            <div class="customer-sub">{{ $order->shipping_address }}</div>
            @endif
            @if($order->shipping_area || $order->shipping_district)
            <div class="customer-sub">{{ implode(', ', array_filter([$order->shipping_area, $order->shipping_district])) }}</div>
            @endif
        </div>
        <div class="info-cell">
            <div class="section-label">Order Details</div>
            <table class="detail-table">
                <tr><td>Order #</td><td>{{ $order->order_number }}</td></tr>
                <tr><td>Date</td><td>{{ $order->created_at->format('d M Y') }}</td></tr>
                <tr><td>Status</td><td>{{ ucfirst($order->status) }}</td></tr>
                <tr><td>Payment</td><td>{{ ucfirst($order->payment_method ?? '—') }}</td></tr>
            </table>
        </div>
    </div>

    {{-- Items --}}
    <table class="items">
        <thead>
            <tr>
                <th style="text-align:left; width:30px">#</th>
                <th style="text-align:left">Item Description</th>
                <th style="text-align:center; width:50px">Qty</th>
                <th style="text-align:right; width:80px">Price</th>
                @if($order->discount > 0)<th style="text-align:right; width:70px">Disc.</th>@endif
                <th style="text-align:right; width:90px">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $i => $item)
            <tr>
                <td style="color:#9ca3af; font-size:11px; font-family:monospace;">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</td>
                <td>
                    <div class="product-name">{{ $item->product_name }}</div>
                    @if($item->sku)<div class="product-sku">{{ $item->sku }}</div>@endif
                </td>
                <td class="num">{{ $item->quantity }}</td>
                <td class="right">{{ $business->currency_symbol ?? '৳' }}{{ number_format($item->price, 0) }}</td>
                @if($order->discount > 0)
                <td class="right" style="color:#ef4444;">
                    {{ $item->discount > 0 ? '−'.($business->currency_symbol ?? '৳').number_format($item->discount,0) : '—' }}
                </td>
                @endif
                <td class="total" style="color: {{ $business->themeColor('700') }}">
                    {{ $business->currency_symbol ?? '৳' }}{{ number_format($item->total, 0) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Summary --}}
    <div class="summary-wrap">
        <table class="summary-table">
            <tr class="summary-row"><td>Subtotal</td><td>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->subtotal, 0) }}</td></tr>
            @if($order->discount > 0)
            <tr class="summary-row"><td style="color:#ef4444">Discount</td><td style="color:#ef4444">−{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->discount, 0) }}</td></tr>
            @endif
            @if($order->shipping_charge > 0)
            <tr class="summary-row"><td>Shipping</td><td>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->shipping_charge, 0) }}</td></tr>
            @endif
            @if($order->vat > 0)
            <tr class="summary-row"><td>VAT</td><td>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->vat, 0) }}</td></tr>
            @endif
            <tr class="summary-row summary-total">
                <td>Grand Total</td>
                <td style="color: {{ $business->themeColor('600') }}">{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->total, 0) }}</td>
            </tr>
            @if($order->paid_amount > 0)
            <tr class="summary-row summary-paid"><td>Paid</td><td>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->paid_amount, 0) }}</td></tr>
            @endif
            @if($order->due_amount > 0)
            <tr class="summary-row summary-due"><td>Due</td><td>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->due_amount, 0) }}</td></tr>
            @endif
        </table>
    </div>

    {{-- Notes --}}
    @if($order->notes)
    <div class="notes">
        <div class="notes-label">Notes</div>
        <div class="notes-text">{{ $order->notes }}</div>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-left">
            <div class="footer-biz">{{ $business->name }}</div>
            <div class="footer-sub">{{ implode(' · ', array_filter([$business->phone, $business->email])) }}</div>
        </div>
        <div class="footer-right">
            <div class="footer-thanks">Thank you for your business!</div>
            <div class="footer-gen">Generated {{ now()->format('d M Y, H:i') }}</div>
        </div>
    </div>

</div>
</body>
</html>
