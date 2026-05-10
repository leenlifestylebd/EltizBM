<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label - {{ $shipment->consignment_id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; }

        .label {
            width: 2in;
            height: 3in;
            border: 2px solid #000;
            padding: 6px;
            page-break-after: always;
            overflow: hidden;
        }

        .merchant-header {
            border-bottom: 1.5px solid #000;
            padding-bottom: 4px;
            margin-bottom: 4px;
        }

        .merchant-name {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .merchant-phone {
            font-size: 9px;
            color: #333;
        }

        .cons-section {
            border-bottom: 1.5px solid #000;
            padding-bottom: 4px;
            margin-bottom: 4px;
        }

        .cons-id {
            font-size: 10px;
            font-weight: bold;
        }

        .tracking {
            font-size: 8px;
            color: #333;
        }

        .to-section {
            border-bottom: 1.5px solid #000;
            padding-bottom: 4px;
            margin-bottom: 4px;
        }

        .to-label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
        }

        .customer-name {
            font-size: 11px;
            font-weight: bold;
        }

        .customer-phone {
            font-size: 10px;
            font-weight: bold;
        }

        .customer-address {
            font-size: 8px;
            color: #333;
            margin-top: 1px;
        }

        .items-section {
            border-bottom: 1.5px solid #000;
            padding-bottom: 4px;
            margin-bottom: 4px;
        }

        .items-label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
        }

        .item-row {
            font-size: 8px;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cod-section {
            text-align: center;
            background: #000;
            color: #fff;
            padding: 3px;
            border-radius: 2px;
        }

        .cod-label {
            font-size: 8px;
        }

        .cod-amount {
            font-size: 14px;
            font-weight: bold;
        }

        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            .label { border: 2px solid #000; }
        }
    </style>
</head>
<body>

{{-- Print Button --}}
<div class="no-print" style="padding: 10px; text-align: center;">
    <button onclick="window.print()" style="padding: 8px 20px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px;">
        🖨️ Print Label
    </button>
    <a href="{{ route('steadfast.index') }}" style="padding: 8px 20px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none;">
        ← Back
    </a>
</div>

{{-- Label --}}
<div class="label">

    {{-- Merchant --}}
    <div class="merchant-header">
        <div class="merchant-name">{{ $business->name }}</div>
        <div class="merchant-phone">{{ $business->phone }}</div>
    </div>

    {{-- Consignment --}}
    <div class="cons-section">
        <div class="cons-id">CONS: {{ $shipment->consignment_id ?? 'N/A' }}</div>
        <div class="tracking">TRACK: {{ $shipment->tracking_id ?? 'N/A' }}</div>
    </div>

    {{-- Customer --}}
    <div class="to-section">
        <div class="to-label">TO:</div>
        <div class="customer-name">{{ $shipment->order->customer_name }}</div>
        <div class="customer-phone">{{ $shipment->order->customer_phone }}</div>
        <div class="customer-address">
            {{ implode(', ', array_filter([
                $shipment->order->shipping_address,
                $shipment->order->shipping_area,
                $shipment->order->shipping_district,
            ])) }}
        </div>
    </div>

    {{-- Items --}}
    <div class="items-section">
        <div class="items-label">ITEMS:</div>
        @foreach($shipment->order->items->take(4) as $item)
        <div class="item-row">{{ Str::limit($item->product_name, 22) }} ×{{ $item->quantity }}</div>
        @endforeach
        @if($shipment->order->items->count() > 4)
        <div class="item-row">+{{ $shipment->order->items->count() - 4 }} more items</div>
        @endif
    </div>

    {{-- COD --}}
    <div class="cod-section">
        <div class="cod-label">COD AMOUNT</div>
        <div class="cod-amount">৳{{ number_format($shipment->cod_amount) }}</div>
    </div>

</div>

</body>
</html>