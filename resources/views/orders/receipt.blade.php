<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt #{{ $order->order_number }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 12px;
        background: #f3f4f6;
        display: flex;
        justify-content: center;
        padding: 20px;
    }
    .receipt {
        background: white;
        width: 300px;
        padding: 16px;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    .divider { border-top: 1px dashed #ccc; margin: 8px 0; }
    .divider-solid { border-top: 1px solid #999; margin: 8px 0; }
    .row { display: flex; justify-content: space-between; margin: 3px 0; }
    .row .label { color: #555; }
    .biz-name { font-size: 16px; font-weight: bold; text-align: center; margin-bottom: 2px; }
    .biz-sub { font-size: 11px; text-align: center; color: #555; }
    .inv-num { font-size: 14px; font-weight: bold; text-align: center; margin: 6px 0 2px; }
    .item-name { font-weight: bold; }
    .item-detail { display: flex; justify-content: space-between; color: #555; font-size: 11px; padding-left: 8px; }
    .total-row { display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin: 4px 0; }
    .paid-row  { display: flex; justify-content: space-between; color: #16a34a; font-weight: bold; }
    .due-row   { display: flex; justify-content: space-between; color: #dc2626; font-weight: bold; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; letter-spacing: 1px; }
    .badge-paid    { background: #dcfce7; color: #16a34a; }
    .badge-partial { background: #fef9c3; color: #ca8a04; }
    .badge-unpaid  { background: #fee2e2; color: #dc2626; }
    .thank-you { font-size: 13px; font-weight: bold; text-align: center; margin: 4px 0 2px; }
    .footer-sub { font-size: 10px; text-align: center; color: #888; }
    .no-print { margin-bottom: 12px; text-align: center; }
    .btn { display: inline-block; padding: 6px 16px; border-radius: 6px; font-size: 12px; text-decoration: none; cursor: pointer; border: none; font-family: Arial, sans-serif; }
    .btn-print { background: #1f2937; color: white; margin-right: 6px; }
    .btn-back  { background: #f3f4f6; color: #374151; }
    @media print {
        body { background: white; padding: 0; }
        .receipt { box-shadow: none; border-radius: 0; margin: 0 auto; }
        .no-print { display: none !important; }
        @page { margin: 0; size: 80mm auto; }
    }
</style>
</head>
<body>

<div>
    {{-- Buttons --}}
    <div class="no-print">
        <button class="btn btn-print" onclick="window.print()">🖨 Print Receipt</button>
        <a class="btn btn-back" href="{{ route('orders.invoice', $order) }}">← Invoice</a>
    </div>

    {{-- Receipt --}}
    <div class="receipt">

        {{-- Business Header --}}
        <div class="biz-name">{{ $business->name }}</div>
        @if($business->phone)
        <div class="biz-sub">{{ $business->phone }}</div>
        @endif
        @if($business->address)
        <div class="biz-sub">{{ $business->address }}</div>
        @endif

        <div class="divider-solid"></div>

        <div class="inv-num">RECEIPT #{{ $order->order_number }}</div>
        <div class="center" style="font-size:11px; color:#666; margin-bottom:4px;">{{ $order->created_at->format('d M Y, h:i A') }}</div>

        <div class="divider"></div>

        {{-- Customer --}}
        <div class="row"><span class="label">Customer:</span><span class="bold">{{ $order->customer_name }}</span></div>
        @if($order->customer_phone)
        <div class="row"><span class="label">Phone:</span><span>{{ $order->customer_phone }}</span></div>
        @endif

        <div class="divider"></div>

        {{-- Items --}}
        @foreach($order->items as $item)
        <div style="margin-bottom: 5px;">
            <div class="item-name">{{ $item->product_name }}</div>
            <div class="item-detail">
                <span>{{ $item->quantity }} x {{ $business->currency_symbol ?? '৳' }}{{ number_format($item->price, 0) }}</span>
                <span class="bold" style="color:#111">{{ $business->currency_symbol ?? '৳' }}{{ number_format($item->total, 0) }}</span>
            </div>
        </div>
        @endforeach

        <div class="divider"></div>

        {{-- Totals --}}
        @if($order->discount > 0)
        <div class="row"><span>Discount:</span><span style="color:#ef4444">−{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->discount, 0) }}</span></div>
        @endif
        @if($order->shipping_charge > 0)
        <div class="row"><span>Shipping:</span><span>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->shipping_charge, 0) }}</span></div>
        @endif
        @if($order->vat > 0)
        <div class="row"><span>VAT:</span><span>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->vat, 0) }}</span></div>
        @endif

        <div class="divider-solid"></div>

        <div class="total-row">
            <span>TOTAL</span>
            <span>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->total, 0) }}</span>
        </div>

        @if($order->paid_amount > 0)
        <div class="paid-row"><span>Paid ({{ ucfirst($order->payment_method ?? 'cash') }})</span><span>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->paid_amount, 0) }}</span></div>
        @endif
        @if($order->due_amount > 0)
        <div class="due-row"><span>Due</span><span>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->due_amount, 0) }}</span></div>
        @endif

        <div class="divider"></div>

        {{-- Payment Status --}}
        @php $badgeClass = match($order->payment_status) { 'paid'=>'badge-paid','partial'=>'badge-partial',default=>'badge-unpaid' }; @endphp
        <div class="center" style="margin: 6px 0;">
            <span class="badge {{ $badgeClass }}">{{ strtoupper($order->payment_status ?? 'UNPAID') }}</span>
        </div>

        @if($order->notes)
        <div class="divider"></div>
        <div style="font-size:11px; color:#555;">Note: {{ $order->notes }}</div>
        @endif

        <div class="divider"></div>

        <div class="thank-you">Thank You! Come Again</div>
        @if($business->phone)
        <div class="footer-sub">{{ $business->phone }}</div>
        @endif
        <div class="footer-sub" style="margin-top:8px; font-size:9px;">★ ★ ★</div>

    </div>
</div>

</body>
</html>
