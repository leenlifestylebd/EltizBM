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
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11px;
    color: #1e293b;
    background: #fff;
    width: 210mm;
}

/* ── Each half ────────────────────────────── */
.slip {
    width: 100%;
    height: 138mm;
    padding: 10mm 12mm;
    overflow: hidden;
}

/* ── Divider ──────────────────────────────── */
.divider {
    width: 100%;
    height: 6mm;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border-top: 1.5px dashed #94a3b8;
    border-bottom: 1.5px dashed #94a3b8;
    background: #f8fafc;
}
.divider span {
    font-size: 8px;
    color: #94a3b8;
    letter-spacing: 1px;
    text-transform: uppercase;
}

/* ── Header ───────────────────────────────── */
.slip-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 5mm;
    border-bottom: 2px solid {{ $pc }};
    margin-bottom: 4mm;
}
.biz-name {
    font-size: 14px;
    font-weight: 700;
    color: {{ $pc }};
}
.biz-sub {
    font-size: 9px;
    color: #64748b;
    margin-top: 2px;
}
.copy-label {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 3px 8px;
    border-radius: 4px;
    color: #fff;
    background: {{ $pc }};
}
.order-num {
    font-size: 11px;
    font-weight: 700;
    color: #1e293b;
    margin-top: 3px;
    text-align: right;
}

/* ── Body grid ────────────────────────────── */
.body-grid {
    display: flex;
    gap: 5mm;
}
.col-left  { flex: 1.1; }
.col-right { flex: 0.9; }

/* ── Section ──────────────────────────────── */
.section-title {
    font-size: 8px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: {{ $pc }};
    margin-bottom: 2.5mm;
    margin-top: 3mm;
    border-bottom: 1px solid {{ $p50 }};
    padding-bottom: 1px;
}

/* ── Info rows ────────────────────────────── */
.info-row {
    display: flex;
    justify-content: space-between;
    font-size: 10px;
    margin-bottom: 1.5mm;
}
.info-label { color: #64748b; }
.info-value { font-weight: 600; color: #1e293b; }

/* ── Measurements grid ────────────────────── */
.meas-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5mm;
}
.meas-cell {
    width: 22%;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 3px;
    padding: 2mm 2.5mm;
    text-align: center;
}
.meas-cell .lbl { font-size: 7.5px; color: #64748b; }
.meas-cell .val { font-size: 10px; font-weight: 700; color: #1e293b; margin-top: 1px; }

/* ── Payment box ──────────────────────────── */
.pay-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 3mm;
    margin-top: 3mm;
}
.pay-row {
    display: flex;
    justify-content: space-between;
    font-size: 10px;
    margin-bottom: 1.5mm;
}
.pay-row:last-child { margin-bottom: 0; }
.pay-divider { border: none; border-top: 1px solid #e2e8f0; margin: 1.5mm 0; }
.due-row .pay-label { font-weight: 700; color: #1e293b; }
.due-row .pay-val   { font-weight: 700; color: {{ $tailoring->due_amount > 0 ? '#dc2626' : '#16a34a' }}; font-size: 11px; }

/* ── Design note ──────────────────────────── */
.note-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 3px;
    padding: 2mm 3mm;
    font-size: 9.5px;
    color: #92400e;
    margin-top: 2mm;
    line-height: 1.4;
}

/* ── Delivery badge ───────────────────────── */
.delivery-badge {
    display: inline-block;
    background: {{ $p50 }};
    border: 1px solid {{ $pc }};
    border-radius: 3px;
    padding: 2px 6px;
    font-size: 9px;
    font-weight: 700;
    color: {{ $pc }};
    margin-top: 2mm;
}

/* ── Stage badge ──────────────────────────── */
.stage-badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 10px;
    font-size: 9px;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
}

/* ── Signature line ───────────────────────── */
.sig-line {
    margin-top: 4mm;
    border-top: 1px dashed #cbd5e1;
    padding-top: 1.5mm;
    font-size: 8px;
    color: #94a3b8;
    text-align: center;
}
</style>
</head>
<body>

@for($copy = 1; $copy <= 2; $copy++)
@php $isOffice = $copy === 1; @endphp

<div class="slip">

    {{-- Header --}}
    <div class="slip-header">
        <div>
            <div class="biz-name">{{ $biz?->name ?? 'Business Name' }}</div>
            @if($biz?->phone)<div class="biz-sub">📞 {{ $biz->phone }}</div>@endif
            @if($biz?->address)<div class="biz-sub">📍 {{ $biz->address }}</div>@endif
        </div>
        <div style="text-align:right">
            <div class="copy-label">{{ $isOffice ? 'Office Copy' : 'Customer Copy' }}</div>
            <div class="order-num">{{ $tailoring->order_number }}</div>
            <div style="font-size:8.5px;color:#64748b;margin-top:2px;">{{ $tailoring->created_at->format('d M Y') }}</div>
        </div>
    </div>

    <div class="body-grid">

        {{-- LEFT COLUMN --}}
        <div class="col-left">

            {{-- Customer --}}
            <div class="section-title">Customer Info</div>
            <div class="info-row">
                <span class="info-label">Name</span>
                <span class="info-value">{{ $tailoring->customer_name }}</span>
            </div>
            @if($tailoring->customer_phone)
            <div class="info-row">
                <span class="info-label">Phone</span>
                <span class="info-value">{{ $tailoring->customer_phone }}</span>
            </div>
            @endif

            {{-- Measurements --}}
            @if($hasMeasurements)
            <div class="section-title">Measurements (inches)</div>
            <div class="meas-grid">
                @foreach($measurements as $label => $val)
                @if($val)
                <div class="meas-cell">
                    <div class="lbl">{{ $label }}</div>
                    <div class="val">{{ number_format($val, 1) }}"</div>
                </div>
                @endif
                @endforeach
            </div>
            @endif

            {{-- Measurement note --}}
            @if($tailoring->measurement_note)
            <div class="note-box" style="margin-top:2mm;">📝 {{ $tailoring->measurement_note }}</div>
            @endif

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="col-right">

            {{-- Fabric & Design --}}
            <div class="section-title">Fabric & Design</div>
            @if($tailoring->fabric_type)
            <div class="info-row">
                <span class="info-label">Fabric</span>
                <span class="info-value">{{ $tailoring->fabric_type }}</span>
            </div>
            @endif
            @if($tailoring->color)
            <div class="info-row">
                <span class="info-label">Color</span>
                <span class="info-value">{{ $tailoring->color }}</span>
            </div>
            @endif
            @if($tailoring->design_note)
            <div class="note-box">✂️ {{ Str::limit($tailoring->design_note, 120) }}</div>
            @endif

            {{-- Delivery --}}
            @if($tailoring->delivery_date)
            <div class="section-title" style="margin-top:3mm;">Delivery</div>
            <div class="delivery-badge">📅 {{ $tailoring->delivery_date->format('d M Y') }}</div>
            @endif

            {{-- Stage --}}
            <div style="margin-top:2mm;">
                <span class="stage-badge">{{ $tailoring->stage_info['icon'] }} {{ $tailoring->stage_info['label'] }}</span>
            </div>

            {{-- Payment --}}
            <div class="section-title" style="margin-top:3mm;">Payment</div>
            <div class="pay-box">
                <div class="pay-row">
                    <span class="pay-label" style="color:#64748b;">Total Price</span>
                    <span class="pay-val" style="font-weight:600;">৳{{ number_format($tailoring->price, 0) }}</span>
                </div>
                <div class="pay-row">
                    <span class="pay-label" style="color:#64748b;">Advance Paid</span>
                    <span class="pay-val" style="color:#16a34a;font-weight:600;">৳{{ number_format($tailoring->advance_paid, 0) }}</span>
                </div>
                <hr class="pay-divider">
                <div class="pay-row due-row">
                    <span class="pay-label">Due Amount</span>
                    <span class="pay-val">৳{{ number_format($tailoring->due_amount, 0) }}</span>
                </div>
            </div>

            {{-- Signature --}}
            <div class="sig-line">Authorized Signature _______________</div>

        </div>
    </div>

</div>

@if($copy === 1)
<div class="divider">
    <span>✂ &nbsp; Cut Here &nbsp; ✂</span>
</div>
@endif

@endfor

</body>
</html>
