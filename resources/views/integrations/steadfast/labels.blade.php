<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Labels — {{ $shipments->count() }} parcels</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/barcodes/JsBarcode.code128.min.js"></script>
    <style>
        @include('integrations.steadfast._label-styles')

        /* Bulk-only overrides */
        body { background: #e5e7eb; }
        .action-bar {
            position: sticky; top: 0; z-index: 10;
            border-bottom: 1px solid #e5e7eb;
            border-radius: 0;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }
        .page-content {
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 0;
        }
        .cut-wrap {
            background: #fff;
            margin: 0;
        }
        .cut-wrap::before { background: #e5e7eb; }

        @media print {
            /* Follow the printer's page setup / selected paper size. */
            @page { size: auto; margin: 0; }
            body { background: #fff !important; margin: 0; }
            .page-content { padding: 0; }
            .cut-wrap {
                display: block;
                padding: 0;
                border: none;
                break-inside: avoid;
                page-break-inside: avoid;
                page-break-after: always;
            }
            .cut-wrap::before { display: none; }
            .label { width: 100%; height: 100vh; border: none; }
        }
    </style>
</head>
<body>

<div class="action-bar no-print">
    <button class="btn-print" onclick="window.print()">
        🖨️ Print {{ $shipments->count() }} Label{{ $shipments->count() > 1 ? 's' : '' }}
    </button>
    <a class="btn-back" href="{{ route('steadfast.index') }}">← Back</a>
    <span class="label-info">{{ $shipments->count() }} labels &nbsp;·&nbsp; fits your printer's page size</span>
</div>

<div class="page-content">
    @foreach($shipments as $shipment)
    <div class="cut-wrap">
        @include('integrations.steadfast._label-body', ['shipment' => $shipment, 'business' => $business])
    </div>
    @endforeach
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
