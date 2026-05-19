@php
    $biz = $tailoring->business ?? auth()->user()->business ?? null;
    $pc  = $biz ? $biz->themeColor('600') : '#2563eb';
    $p50 = $biz ? $biz->themeColor('50')  : '#eff6ff';

    $measurements = [
        'Height'      => $tailoring->height,
        'Body Fit'    => $tailoring->body_fit,
        'Body Loose'  => $tailoring->body_loose,
        'Shoulder'    => $tailoring->shoulder,
        'Sleeve'      => $tailoring->sleeve,
        'Sleeve Open' => $tailoring->sleeve_open,
        'Neck'        => $tailoring->neck,
        'Round Gher'  => $tailoring->round_gher,
    ];
    $hasMeasurements = collect($measurements)->filter()->isNotEmpty();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

html, body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 10px;
    color: #1e293b;
    background: #fff;
    width: 210mm;
    height: 297mm;
    overflow: hidden;
}

/* Wrapper — exactly one A4 page */
.page {
    width: 210mm;
    height: 297mm;
    display: block;
    overflow: hidden;
}

/* Each copy = ~143mm tall */
.slip {
    width: 210mm;
    height: 143mm;
    padding: 6mm 10mm;
    overflow: hidden;
    display: block;
}

/* Cut line divider — ~5mm */
.divider {
    width: 210mm;
    height: 5mm;
    border-top: 1px dashed #94a3b8;
    border-bottom: 1px dashed #94a3b8;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
}
.divider span {
    font-size: 7px;
    color: #94a3b8;
    letter-spacing: 2px;
    text-transform: uppercase;
}

/* Header */
.slip-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid {{ $pc }};
    padding-bottom: 3mm;
    margin-bottom: 3mm;
}
.biz-name { font-size: 13px; font-weight: 700; color: {{ $pc }}; }
.biz-sub  { font-size: 8px; color: #64748b; margin-top: 1px; }
.copy-badge {
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    background: {{ $pc }};
    color: #fff;
    padding: 2px 7px;
    border-radius: 3px;
    display: inline-block;
}
.order-info {
    text-align: right;
    margin-top: 2px;
    font-size: 9px;
    color: #475569;
    font-weight: 600;
}

/* Two column body */
.body {
    display: flex;
    gap: 5mm;
}
.col-l { flex: 1.15; }
.col-r { flex: 0.85; }

/* Section label */
.sec {
    font-size: 7.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: {{ $pc }};
    border-bottom: 1px solid {{ $p50 }};
    padding-bottom: 1px;
    margin-bottom: 2mm;
    margin-top: 2.5mm;
}

/* Info row */
.row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1.5mm;
    font-size: 9.5px;
}
.lbl { color: #64748b; }
.val { font-weight: 600; color: #1e293b; }

/* Measurement grid */
.meas {
    display: flex;
    flex-wrap: wrap;
    gap: 1.2mm;
}
.mc {
    width: 23%;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 2px;
    padding: 1.5mm 2mm;
    text-align: center;
}
.mc .ml { font-size: 7px; color: #64748b; }
.mc .mv { font-size: 9px; font-weight: 700; margin-top: 1px; }

/* Note box */
.note {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 2px;
    padding: 1.5mm 2.5mm;
    font-size: 8.5px;
    color: #92400e;
    margin-top: 2mm;
    line-height: 1.4;
}

/* Payment box */
.pay {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 3px;
    padding: 2.5mm;
    margin-top: 2mm;
}
.prow {
    display: flex;
    justify-content: space-between;
    font-size: 9.5px;
    margin-bottom: 1.5mm;
}
.prow:last-child { margin-bottom: 0; }
.phr { border: none; border-top: 1px solid #e2e8f0; margin: 1.5mm 0; }
.due-lbl { font-weight: 700; }
.due-val { font-weight: 700; font-size: 10.5px; color: {{ $tailoring->due_amount > 0 ? '#dc2626' : '#16a34a' }}; }

/* Delivery */
.dlv {
    display: inline-block;
    border: 1px solid {{ $pc }};
    background: {{ $p50 }};
    color: {{ $pc }};
    font-size: 8.5px;
    font-weight: 700;
    border-radius: 3px;
    padding: 1.5px 5px;
    margin-top: 2mm;
}

/* Stage */
.stage {
    display: inline-block;
    background: #f1f5f9;
    color: #475569;
    font-size: 8px;
    font-weight: 700;
    border-radius: 10px;
    padding: 1.5px 6px;
    margin-top: 1.5mm;
}

/* Signature */
.sig {
    border-top: 1px dashed #cbd5e1;
    margin-top: 3mm;
    padding-top: 1.5mm;
    font-size: 7.5px;
    color: #94a3b8;
    text-align: center;
}
</style>
</head>
<body>
<div class="page">

@for($copy = 1; $copy <= 2; $copy++)
@php $isOffice = ($copy === 1); @endphp

<div class="slip">

    {{-- Header --}}
    <div class="slip-header">
        <div>
            <div class="biz-name">{{ $biz?->name ?? 'Business Name' }}</div>
            @if($biz?->phone)<div class="biz-sub">📞 {{ $biz->phone }}</div>@endif
            @if($biz?->address)<div class="biz-sub">📍 {{ $biz->address }}</div>@endif
        </div>
        <div>
            <div style="text-align:right"><span class="copy-badge">{{ $isOffice ? 'Office Copy' : 'Customer Copy' }}</span></div>
            <div class="order-info">{{ $tailoring->order_number }} &nbsp;|&nbsp; {{ $tailoring->created_at->format('d M Y') }}</div>
        </div>
    </div>

    {{-- Body --}}
    <div class="body">

        {{-- LEFT --}}
        <div class="col-l">
            <div class="sec">Customer</div>
            <div class="row"><span class="lbl">Name</span><span class="val">{{ $tailoring->customer_name }}</span></div>
            @if($tailoring->customer_phone)
            <div class="row"><span class="lbl">Phone</span><span class="val">{{ $tailoring->customer_phone }}</span></div>
            @endif

            @if($hasMeasurements)
            <div class="sec">Measurements (inches)</div>
            <div class="meas">
                @foreach($measurements as $label => $val)
                @if($val)
                <div class="mc">
                    <div class="ml">{{ $label }}</div>
                    <div class="mv">{{ number_format($val, 1) }}"</div>
                </div>
                @endif
                @endforeach
            </div>
            @endif

            @if($tailoring->measurement_note)
            <div class="note">📝 {{ $tailoring->measurement_note }}</div>
            @endif
        </div>

        {{-- RIGHT --}}
        <div class="col-r">
            <div class="sec">Fabric & Design</div>
            @if($tailoring->fabric_type)
            <div class="row"><span class="lbl">Fabric</span><span class="val">{{ $tailoring->fabric_type }}</span></div>
            @endif
            @if($tailoring->color)
            <div class="row"><span class="lbl">Color</span><span class="val">{{ $tailoring->color }}</span></div>
            @endif
            @if($tailoring->design_note)
            <div class="note">✂️ {{ Str::limit($tailoring->design_note, 100) }}</div>
            @endif

            @if($tailoring->delivery_date)
            <div class="sec">Delivery Date</div>
            <div class="dlv">📅 {{ $tailoring->delivery_date->format('d M Y') }}</div>
            @endif

            <div class="stage">{{ $tailoring->stage_info['icon'] }} {{ $tailoring->stage_info['label'] }}</div>

            <div class="sec">Payment</div>
            <div class="pay">
                <div class="prow"><span style="color:#64748b">Total</span><span>৳{{ number_format($tailoring->price, 0) }}</span></div>
                <div class="prow"><span style="color:#64748b">Received</span><span style="color:#16a34a;font-weight:600">৳{{ number_format($tailoring->advance_paid, 0) }}</span></div>
                <hr class="phr">
                <div class="prow"><span class="due-lbl">Due</span><span class="due-val">৳{{ number_format($tailoring->due_amount, 0) }}</span></div>
            </div>

            <div class="sig">Signature ___________________</div>
        </div>

    </div>
</div>

@if($copy === 1)
<div class="divider"><span>✂ &nbsp;&nbsp; cut here &nbsp;&nbsp; ✂</span></div>
@endif

@endfor

</div>
</body>
</html>
