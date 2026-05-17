<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label — #{{ $shipment->order->order_number ?? $shipment->consignment_id }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.code128.min.js"></script>
    <style>
        @include('integrations.steadfast._label-styles')
    </style>
</head>
<body>

<div class="preview-wrap">
    {{-- Action bar --}}
    <div class="action-bar no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Print Label</button>
        <a class="btn-back" href="{{ route('steadfast.index') }}">← Back</a>
        <span class="label-info">2 × 3 in &nbsp;·&nbsp; DIY cut guide</span>
    </div>

    <div class="cut-wrap">
        @include('integrations.steadfast._label-body', ['shipment' => $shipment, 'business' => $business])
    </div>
</div>

<script>
document.querySelectorAll('.barcode-svg[data-tracking]').forEach(function(el) {
    try {
        JsBarcode(el, el.dataset.tracking, {
            format: 'CODE128',
            width: 1.6,
            height: 44,
            displayValue: false,
            margin: 0,
            background: '#ffffff',
            lineColor: '#000000',
        });
    } catch(e) {}
});
</script>
</body>
</html>
