<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $shipment->order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #111; background: #fff; }
        .page { max-width: 750px; margin: 0 auto; padding: 30px; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #111; }
        .business-name { font-size: 22px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .business-info { font-size: 11px; color: #555; margin-top: 4px; line-height: 1.6; }
        .invoice-label { font-size: 28px; font-weight: bold; text-align: right; color: #111; }
        .invoice-meta { font-size: 11px; color: #555; text-align: right; margin-top: 4px; line-height: 1.6; }

        .addresses { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        .address-label { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #888; letter-spacing: 0.5px; margin-bottom: 6px; }
        .address-name { font-size: 14px; font-weight: bold; }
        .address-detail { font-size: 12px; color: #444; line-height: 1.6; margin-top: 2px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead tr { background: #111; color: #fff; }
        thead th { padding: 8px 10px; text-align: left; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        thead th:last-child { text-align: right; }
        tbody tr { border-bottom: 1px solid #eee; }
        tbody tr:nth-child(even) { background: #f9f9f9; }
        tbody td { padding: 8px 10px; font-size: 12px; }
        tbody td:last-child { text-align: right; font-weight: 500; }

        .totals { display: flex; justify-content: flex-end; margin-bottom: 24px; }
        .totals-table { width: 220px; }
        .totals-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; border-bottom: 1px solid #eee; }
        .totals-row.grand { font-size: 15px; font-weight: bold; border-top: 2px solid #111; border-bottom: none; padding-top: 8px; margin-top: 4px; }

        .footer { border-top: 1px solid #ddd; padding-top: 12px; text-align: center; font-size: 11px; color: #888; }

        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print" style="background:#f3f4f6; padding:12px; text-align:center; margin-bottom:10px;">
    <button onclick="window.print()" style="padding:8px 20px; background:#111; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:14px; margin-right:8px;">🖨️ Print Invoice</button>
    <a href="{{ route('steadfast.label', $shipment) }}" target="_blank" style="padding:8px 20px; background:#059669; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:14px; text-decoration:none; margin-right:8px;">🏷️ Print Label</a>
    <a href="{{ route('steadfast.index') }}" style="padding:8px 20px; background:#6b7280; color:#fff; border:none; border-radius:6px; font-size:14px; text-decoration:none;">← Back</a>
</div>

<div class="page">

    <div class="header">
        <div>
            <div class="business-name">{{ $business->name }}</div>
            <div class="business-info">
                {{ $business->phone }}<br>
                {{ $business->email }}<br>
                {{ $business->address }}
            </div>
        </div>
        <div>
            <div class="invoice-label">INVOICE</div>
            <div class="invoice-meta">
                #{{ $shipment->order->order_number }}<br>
                {{ $shipment->order->created_at->format('d M Y') }}<br>
                @if($shipment->order->payment_method)
                {{ ucfirst($shipment->order->payment_method) }}
                @endif
            </div>
        </div>
    </div>

    <div class="addresses">
        <div>
            <div class="address-label">From</div>
            <div class="address-name">{{ $business->name }}</div>
            <div class="address-detail">
                {{ $business->phone }}<br>
                {{ $business->address }}
            </div>
        </div>
        <div>
            <div class="address-label">To</div>
            <div class="address-name">{{ $shipment->order->customer_name }}</div>
            <div class="address-detail">
                {{ $shipment->order->customer_phone }}<br>
                {{ implode(', ', array_filter([
                    $shipment->order->shipping_address,
                    $shipment->order->shipping_area,
                    $shipment->order->shipping_district,
                ])) }}
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shipment->order->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>৳{{ number_format($item->price) }}</td>
                <td>৳{{ number_format($item->price * $item->quantity) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-table">
            <div class="totals-row">
                <span>Subtotal</span>
                <span>৳{{ number_format($shipment->order->subtotal) }}</span>
            </div>
            @if($shipment->order->discount > 0)
            <div class="totals-row">
                <span>Discount</span>
                <span>-৳{{ number_format($shipment->order->discount) }}</span>
            </div>
            @endif
            @if($shipment->order->shipping_charge > 0)
            <div class="totals-row">
                <span>Shipping</span>
                <span>৳{{ number_format($shipment->order->shipping_charge) }}</span>
            </div>
            @endif
            <div class="totals-row grand">
                <span>Total</span>
                <span>৳{{ number_format($shipment->order->total) }}</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Thank you for your purchase! &nbsp;|&nbsp; {{ $business->name }} &nbsp;|&nbsp; {{ $business->phone }}</p>
    </div>

</div>
</body>
</html>