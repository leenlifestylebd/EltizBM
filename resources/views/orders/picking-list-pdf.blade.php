<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Picking List</title>
<style>
    /* Bengali-capable font so customer names / addresses render (DejaVu lacks
       Bengali glyphs and shows tofu boxes otherwise). */
    @font-face {
        font-family: 'Hind Siliguri';
        font-weight: normal;
        font-style: normal;
        src: url("{{ public_path('fonts/HindSiliguri-Regular.ttf') }}") format('truetype');
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Hind Siliguri', 'DejaVu Sans', sans-serif; color: #111; font-size: 11px; }
    .wrap { padding: 18px 22px; }

    .head { border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 12px; }
    .head h1 { font-size: 18px; letter-spacing: .5px; }
    .head .biz { font-size: 12px; font-weight: bold; text-transform: uppercase; }
    .head .meta { font-size: 10px; color: #555; margin-top: 3px; }

    .section-title {
        background: #111; color: #fff; font-size: 11px; font-weight: bold;
        text-transform: uppercase; letter-spacing: .5px; padding: 5px 8px; margin: 14px 0 6px;
    }

    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; vertical-align: top; }
    th { background: #f0f0f0; font-size: 9px; text-transform: uppercase; letter-spacing: .3px; color: #444; }
    td.qty, th.qty { text-align: center; width: 48px; font-weight: bold; }
    td.sku, th.sku { font-family: 'DejaVu Sans Mono', monospace; font-size: 9.5px; color: #333; width: 90px; }
    .muted { color: #888; font-size: 9px; }

    /* per-order block */
    .order-card { margin-bottom: 10px; page-break-inside: avoid; }
    .order-head {
        background: #f6f6f6; border: 1px solid #ccc; border-bottom: none;
        padding: 5px 8px; font-size: 10.5px;
    }
    .order-head .onum { font-weight: bold; font-size: 12px; }
    .order-head .cust { float: right; text-align: right; }
    .order-head .cust b { font-size: 11px; }
    .order-card table { border-top: none; }
    .box-checkbox { display: inline-block; width: 11px; height: 11px; border: 1.3px solid #333; }

    .totals { margin-top: 4px; font-size: 10px; text-align: right; color: #333; }
</style>
</head>
<body>
<div class="wrap">

    <div class="head">
        <span class="biz">{{ $business->name ?? 'My Business' }}</span>
        <h1>Picking List</h1>
        <div class="meta">
            {{ now()->format('d M Y, h:i A') }}
            &nbsp;·&nbsp; {{ $orders->count() }} order(s)
            &nbsp;·&nbsp; {{ $totalItems }} item(s) total
        </div>
    </div>

    {{-- ① CONSOLIDATED PICK SUMMARY --}}
    <div class="section-title">① Pick Summary — pull this much stock in total</div>
    <table>
        <thead>
            <tr>
                <th style="width:26px;">✓</th>
                <th>Product</th>
                <th class="sku">SKU</th>
                <th class="qty">Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary as $row)
            <tr>
                <td style="text-align:center;"><span class="box-checkbox"></span></td>
                <td>{{ $row['name'] }}</td>
                <td class="sku">{{ $row['sku'] ?: '—' }}</td>
                <td class="qty">{{ $row['qty'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="totals">Total pieces to pack: <b>{{ $totalItems }}</b></div>

    {{-- ② PER-ORDER BREAKDOWN --}}
    <div class="section-title">② Per-order breakdown — what goes in each parcel</div>

    @foreach($orders as $order)
    <div class="order-card">
        <div class="order-head">
            <span class="cust">
                <b>{{ $order->customer_name ?: 'Walk-in' }}</b><br>
                <span class="muted">{{ $order->customer_phone ?: '' }}</span>
            </span>
            <span class="onum">#{{ $order->order_number }}</span>
            <span class="muted">&nbsp; {{ ucfirst($order->source) }} · {{ $order->created_at->format('d M') }}</span>
            @if($order->shipping_address)
                <div class="muted" style="margin-top:3px; clear:both;">📍 {{ $order->shipping_address }}{{ $order->shipping_area ? ', '.$order->shipping_area : '' }}{{ $order->shipping_district ? ', '.$order->shipping_district : '' }}</div>
            @endif
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:26px;">✓</th>
                    <th>Product</th>
                    <th class="sku">SKU</th>
                    <th class="qty">Qty</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $it)
                <tr>
                    <td style="text-align:center;"><span class="box-checkbox"></span></td>
                    <td>{{ $it->product_name ?: ($it->product?->name ?: 'Item') }}{{ $it->variant?->name ? ' — '.$it->variant->name : '' }}</td>
                    <td class="sku">{{ $it->sku ?: ($it->variant?->sku ?: ($it->product?->sku ?: '—')) }}</td>
                    <td class="qty">{{ (int) $it->quantity }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="muted">No line items.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($order->notes)
            <div class="muted" style="margin-top:3px;">📝 {{ $order->notes }}</div>
        @endif
    </div>
    @endforeach

</div>
</body>
</html>
