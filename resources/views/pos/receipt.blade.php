<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $order->order_number }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Courier New',monospace; }
        body { background:#f5f5f5; display:flex; flex-direction:column; align-items:center; padding:20px; }
        .receipt { background:white; width:300px; padding:16px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
        .header { text-align:center; border-bottom:1px dashed #ccc; padding-bottom:10px; margin-bottom:10px; }
        .header h1 { font-size:18px; font-weight:bold; }
        .header p  { font-size:11px; color:#555; margin-top:2px; }
        .info .row { display:flex; justify-content:space-between; font-size:11px; padding:1px 0; }
        .divider { border-top:1px dashed #ccc; margin:8px 0; }
        .item { font-size:11px; margin-bottom:5px; }
        .item .name { font-weight:bold; }
        .item .detail { display:flex; justify-content:space-between; color:#555; }
        .totals .row { display:flex; justify-content:space-between; font-size:11px; padding:2px 0; }
        .totals .grand { font-size:15px; font-weight:bold; border-top:2px solid #000; margin-top:4px; padding-top:4px; }
        .footer { text-align:center; font-size:10px; color:#888; border-top:1px dashed #ccc; padding-top:10px; margin-top:10px; }
        .print-btn { margin-bottom:16px; padding:10px 32px; background:#2563eb; color:white; border:none; border-radius:8px; font-size:14px; cursor:pointer; font-family:Arial,sans-serif; }
        @media print { body{background:white;padding:0;} .receipt{box-shadow:none;} .print-btn{display:none!important;} }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Print Receipt</button>
    <div class="receipt">
        <div class="header">
            @if($business->logo)
                <img src="{{ Storage::url($business->logo) }}" style="height:36px;object-fit:contain;margin-bottom:6px;display:block;margin-left:auto;margin-right:auto;">
            @endif
            <h1>{{ $business->name }}</h1>
            @if($business->phone)<p>{{ $business->phone }}</p>@endif
            @if($business->address)<p>{{ $business->address }}</p>@endif
        </div>

        <div class="info" style="margin-bottom:8px;">
            <div class="row"><span>Receipt #</span><span>{{ $order->order_number }}</span></div>
            <div class="row"><span>Date</span><span>{{ $order->created_at->format('d/m/Y h:i A') }}</span></div>
            <div class="row"><span>Payment</span><span>{{ strtoupper($order->payment_method) }}</span></div>
            @if($order->customer_name !== 'Walk-in Customer')
            <div class="row"><span>Customer</span><span>{{ $order->customer_name }}</span></div>
            @endif
        </div>

        <div class="divider"></div>

        @foreach($order->items as $item)
        <div class="item">
            <div class="name">{{ $item->product_name }}</div>
            <div class="detail">
                <span>{{ $item->quantity }} × ৳{{ number_format($item->price) }}</span>
                <span>৳{{ number_format($item->total) }}</span>
            </div>
        </div>
        @endforeach

        <div class="divider"></div>

        <div class="totals">
            <div class="row"><span>Subtotal</span><span>৳{{ number_format($order->subtotal) }}</span></div>
            @if($order->discount > 0)
            <div class="row" style="color:#dc2626"><span>Discount</span><span>-৳{{ number_format($order->discount) }}</span></div>
            @endif
            @if($order->shipping_charge > 0)
            <div class="row"><span>Shipping</span><span>৳{{ number_format($order->shipping_charge) }}</span></div>
            @endif
            <div class="row grand"><span>TOTAL</span><span>৳{{ number_format($order->total) }}</span></div>
            <div class="row" style="color:#16a34a"><span>Paid</span><span>৳{{ number_format($order->paid_amount) }}</span></div>
            @php $change = $order->paid_amount - $order->total; @endphp
            @if($change > 0)
            <div class="row" style="color:#16a34a"><span>Change</span><span>৳{{ number_format($change) }}</span></div>
            @elseif($order->due_amount > 0)
            <div class="row" style="color:#dc2626;font-weight:bold"><span>Due</span><span>৳{{ number_format($order->due_amount) }}</span></div>
            @endif
        </div>

        <div class="footer">
            <p>✦ Thank you for your purchase! ✦</p>
            @if($business->domain)<p style="margin-top:4px">{{ $business->domain }}</p>@endif
        </div>
    </div>
    <script>
        if (document.referrer.includes('/pos')) setTimeout(() => window.print(), 600);
    </script>
</body>
</html>
