<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $shipment->order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; background: #fff; }

        .invoice {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }

        .business-name {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .business-info {
            font-size: 11px;
            color: #333;
            margin-top: 3px;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h2 {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .invoice-meta {
            font-size: 11px;
            color: #333;
            margin-top: 3px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        .info-box {
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 4px;
        }

        .info-box-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 4px;
            border-bottom: 1px solid #eee;
            padding-bottom: 3px;
        }

        .info-box p {
            font-size: 11px;
            margin-top: 2px;
        }

        .info-box .name {
            font-size: 13px;
            font-weight: bold;
        }

        .cons-box {
            background: #f3f4f6;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 12px;
            display: flex;
            gap: 20px;
        }

        .cons-item {
            flex: 1;
        }

        .cons-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
        }

        .cons-value {
            font-size: 12px;
            font-weight: bold;
            font-family: monospace;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        table thead tr {
            background: #000;
            color: #fff;
        }

        table thead th {
            padding: 6px 8px;
            text-align: left;
            font-size: 11px;
        }

        table thead th:last-child,
        table thead th:nth-last-child(2),
        table thead th:nth-last-child(3) {
            text-align: right;
        }

        table tbody tr {
            border-bottom: 1px solid #eee;
        }

        table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        table tbody td {
            padding: 6px 8px;
            font-size: 11px;
        }

        table tbody td:last-child,
        table tbody td:nth-last-child(2),
        table tbody td:nth-last-child(3) {
            text-align: right;
        }

        .totals {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 12px;
        }

        .totals-box {
            width: 200px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 11px;
            border-bottom: 1px solid #eee;
        }

        .totals-row.total {
            font-size: 13px;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: none;
            padding-top: 5px;
        }

        .totals-row.cod {
            font-size: 13px;
            font-weight: bold;
            color: #dc2626;
        }

        .footer {
            border-top: 1px solid #ddd;
            padding-top: 8px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

{{-- Print Button --}}
<div class="no-print" style="padding: 10px; text-align: center; background: #f3f4f6; margin-bottom: 10px;">
    <button onclick="window.print()" style="padding: 8px 20px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px;">
        🖨️ Print Invoice
    </button>
    <a href="{{ route('steadfast.label', $shipment) }}" target="_blank" style="padding: 8px 20px; background: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; margin-right: 8px;">
        🏷️ Print Label
    </a>
    <a href="{{ route('steadfast.index') }}" style="padding: 8px 20px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none;">
        ← Back
    </a>
</div>

<div class="invoice">

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="business-name">{{ $business->name }}</div>
            <div class="business-info">{{ $business->phone }}</div>
            <div class="business-info">{{ $business->email }}</div>
            <div class="business-info">{{ $business->address }}</div>
        </div>
        <div class="invoice-title">
            <h2>Invoice</h2>
            <div class="invoice-meta">Order #{{ $shipment->order->order_number }}</div>
            <div class="invoice-meta">Date: {{ $shipment->order->created_at->format('d M Y') }}</div>
            <div class="invoice-meta">Courier: Steadfast</div>
        </div>
    </div>

    {{-- Consignment Info --}}
    <div class="cons-box">
        <div class="cons-item">
            <div class="cons-label">Consignment ID</div>
            <div class="cons-value">{{ $shipment->consignment_id ?? 'N/A' }}</div>
        </div>
        <div class="cons-item">
            <div class="cons-label">Tracking Code</div>
            <div class="cons-value">{{ $shipment->tracking_id ?? 'N/A' }}</div>
        </div>
        <div class="cons-item">
            <div class="cons-label">Status</div>
            <div class="cons-value">{{ ucfirst(str_replace('_', ' ', $shipment->status)) }}</div>
        </div>
        <div class="cons-item">
            <div class="cons-label">Shipped Date</div>
            <div class="cons-value">{{ $shipment->shipped_at?->format('d M Y') ?? 'N/A' }}</div>
        </div>
    </div>

    {{-- From / To --}}
    <div class="info-grid">
        <div class="info-box">
            <div class="info-box-title">From (Merchant)</div>
            <p class="name">{{ $business->name }}</p>
            <p>{{ $business->phone }}</p>
            <p>{{ $business->address }}</p>
        </div>
        <div class="info-box">
            <div class="info-box-title">To (Customer)</div>
            <p class="name">{{ $shipment->order->customer_name }}</p>
            <p>{{ $shipment->order->customer_phone }}</p>
            <p>{{ implode(', ', array_filter([
                $shipment->order->shipping_address,
                $shipment->order->shipping_area,
                $shipment->order->shipping_district,
            ])) }}</p>
        </div>
    </div>

    {{-- Items Table --}}
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

    {{-- Totals --}}
    <div class="totals">
        <div class="totals-box">
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
            <div class="totals-row total">
                <span>Total</span>
                <span>৳{{ number_format($shipment->order->total) }}</span>
            </div>
            <div class="totals-row cod">
                <span>COD Amount</span>
                <span>৳{{ number_format($shipment->cod_amount) }}</span>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>Thank you for your order! | {{ $business->name }} | {{ $business->phone }}</p>
    </div>

</div>

</body>
</html>