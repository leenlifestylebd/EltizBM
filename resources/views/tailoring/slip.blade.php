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
    $filled = collect($measurements)->filter();
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9.5pt;
    color: #1a1a1a;
    background: #fff;
}

/* ── ONE COPY ─────────────────────────── */
.copy {
    width: 190mm;
    margin: 4mm 10mm;
    border: 1px solid #d1d5db;
    border-radius: 3px;
    overflow: hidden;
}

/* ── TOP BAR ──────────────────────────── */
.top-bar {
    background: {{ $pc }};
    color: #fff;
    padding: 3mm 4mm;
}
.top-bar table { width: 100%; }
.biz-name  { font-size: 13pt; font-weight: 700; }
.biz-sub   { font-size: 7.5pt; opacity: 0.85; margin-top: 1px; }
.copy-lbl  { font-size: 8pt; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
             background: rgba(255,255,255,0.25); padding: 2px 8px; border-radius: 20px; }
.order-num { font-size: 11pt; font-weight: 700; margin-top: 2px; }
.order-dt  { font-size: 7.5pt; opacity: 0.85; }

/* ── SECTION HEADER ───────────────────── */
.sec-hdr {
    background: {{ $p50 }};
    border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    padding: 1.5mm 4mm;
    font-size: 7.5pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: {{ $pc }};
}

/* ── CONTENT AREA ─────────────────────── */
.content { padding: 0; }

/* ── ROW DIVIDER ──────────────────────── */
.row-div { border-top: 1px solid #f3f4f6; }

/* ── INFO TABLE ───────────────────────── */
.info-table { width: 100%; border-collapse: collapse; }
.info-table td { padding: 2mm 4mm; vertical-align: top; }
.info-table .lbl { color: #6b7280; font-size: 8.5pt; width: 28%; }
.info-table .val { font-weight: 600; font-size: 9pt; }
.info-table .border-l { border-left: 1px solid #f3f4f6; }

/* ── MEASUREMENTS ─────────────────────── */
.meas-table { width: 100%; border-collapse: collapse; }
.meas-table td {
    width: 12.5%;
    text-align: center;
    padding: 2mm 1mm;
    border-right: 1px solid #f3f4f6;
    border-bottom: 1px solid #f3f4f6;
}
.meas-table td:last-child { border-right: none; }
.meas-lbl { font-size: 7pt; color: #6b7280; }
.meas-val { font-size: 10pt; font-weight: 700; color: #111827; margin-top: 1px; }
.meas-empty .meas-val { color: #d1d5db; }

/* ── BOTTOM ROW ───────────────────────── */
.bottom-table { width: 100%; border-collapse: collapse; border-top: 1px solid #e5e7eb; }
.bottom-table td { vertical-align: top; padding: 2.5mm 4mm; }
.bottom-table .b-left  { width: 55%; border-right: 1px solid #e5e7eb; }
.bottom-table .b-right { width: 45%; }

/* ── PAY ROWS ─────────────────────────── */
.pay-row { display: block; margin-bottom: 1.5mm; }
.pay-lbl { color: #6b7280; font-size: 8.5pt; }
.pay-val { font-weight: 600; font-size: 9pt; float: right; }
.pay-total-lbl { font-weight: 700; font-size: 9.5pt; }
.pay-total-val { font-weight: 700; font-size: 10.5pt; float: right;
    color: {{ $tailoring->due_amount > 0 ? '#dc2626' : '#16a34a' }}; }
.pay-divider { border: none; border-top: 1px solid #e5e7eb; margin: 2mm 0; }

/* ── NOTE BOX ─────────────────────────── */
.note-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 2px;
    padding: 1.5mm 2.5mm;
    font-size: 8pt;
    color: #92400e;
    line-height: 1.4;
    margin-bottom: 2mm;
}

/* ── DELIVERY BADGE ───────────────────── */
.dlv-badge {
    display: inline-block;
    border: 1px solid {{ $pc }};
    background: {{ $p50 }};
    color: {{ $pc }};
    font-size: 8.5pt;
    font-weight: 700;
    border-radius: 3px;
    padding: 1px 6px;
    margin-bottom: 2mm;
}

/* ── SIGNATURE ────────────────────────── */
.sig {
    margin-top: 4mm;
    border-top: 1px dashed #d1d5db;
    padding-top: 1.5mm;
    font-size: 7.5pt;
    color: #9ca3af;
    text-align: center;
}

/* ── STAGE ────────────────────────────── */
.stage-badge {
    display: inline-block;
    background: #f3f4f6;
    color: #374151;
    font-size: 8pt;
    font-weight: 700;
    border-radius: 10px;
    padding: 1px 7px;
    margin-bottom: 3mm;
}

/* ── CUT LINE ─────────────────────────── */
.cut-line {
    width: 100%;
    text-align: center;
    border-top: 1.5px dashed #9ca3af;
    border-bottom: 1.5px dashed #9ca3af;
    padding: 1.5mm 0;
    margin: 0;
    background: #f9fafb;
    font-size: 7.5pt;
    color: #9ca3af;
    letter-spacing: 2px;
    text-transform: uppercase;
}
</style>
</head>
<body>

@for($copy = 1; $copy <= 2; $copy++)
@php $isOffice = ($copy === 1); @endphp

<div class="copy">

    {{-- TOP BAR --}}
    <div class="top-bar">
        <table><tr>
            <td>
                <div class="biz-name">{{ $biz?->name ?? 'Business Name' }}</div>
                @if($biz?->phone)<div class="biz-sub">📞 {{ $biz->phone }}</div>@endif
                @if($biz?->address)<div class="biz-sub">📍 {{ Str::limit($biz->address, 60) }}</div>@endif
            </td>
            <td style="text-align:right; vertical-align:top;">
                <div class="copy-lbl">{{ $isOffice ? '✦ Office Copy' : '✦ Customer Copy' }}</div>
                <div class="order-num">{{ $tailoring->order_number }}</div>
                <div class="order-dt">{{ $tailoring->created_at->format('d M Y') }}</div>
            </td>
        </tr></table>
    </div>

    {{-- CUSTOMER + FABRIC ROW --}}
    <table class="info-table">
        <tr>
            <td style="width:50%; border-right:1px solid #f3f4f6; padding-bottom:3mm;">
                <div class="sec-hdr" style="margin:-0px -4mm 2mm -4mm; padding-left:4mm;">Customer</div>
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td class="lbl">Name</td>
                        <td class="val">{{ $tailoring->customer_name }}</td>
                    </tr>
                    @if($tailoring->customer_phone)
                    <tr>
                        <td class="lbl">Phone</td>
                        <td class="val">{{ $tailoring->customer_phone }}</td>
                    </tr>
                    @endif
                    @if($tailoring->delivery_date)
                    <tr>
                        <td class="lbl">Delivery</td>
                        <td class="val" style="color:{{ $isOverdue ?? false ? '#dc2626' : $pc }}">
                            📅 {{ $tailoring->delivery_date->format('d M Y') }}
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td class="lbl">Stage</td>
                        <td class="val">{{ $tailoring->stage_info['icon'] }} {{ $tailoring->stage_info['label'] }}</td>
                    </tr>
                </table>
            </td>
            <td style="width:50%; vertical-align:top; padding-bottom:3mm;">
                <div class="sec-hdr" style="margin:-0px -4mm 2mm -4mm; padding-left:4mm;">Fabric & Design</div>
                <table style="width:100%; border-collapse:collapse;">
                    @if($tailoring->fabric_type)
                    <tr>
                        <td class="lbl">Fabric</td>
                        <td class="val">{{ $tailoring->fabric_type }}</td>
                    </tr>
                    @endif
                    @if($tailoring->color)
                    <tr>
                        <td class="lbl">Color</td>
                        <td class="val">{{ $tailoring->color }}</td>
                    </tr>
                    @endif
                    @if($tailoring->design_note)
                    <tr>
                        <td class="lbl" style="vertical-align:top;">Design</td>
                        <td class="val" style="font-size:8pt; font-weight:400; line-height:1.4;">{{ Str::limit($tailoring->design_note, 80) }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- MEASUREMENTS --}}
    @if($filled->isNotEmpty())
    <div class="sec-hdr">Measurements &nbsp;<span style="font-weight:400;text-transform:none;letter-spacing:0;">(inches)</span></div>
    <table class="meas-table">
        <tr>
            @foreach($measurements as $label => $val)
            <td class="{{ $val ? '' : 'meas-empty' }}">
                <div class="meas-lbl">{{ $label }}</div>
                <div class="meas-val">{{ $val ? number_format($val, 1).'"' : '—' }}</div>
            </td>
            @endforeach
        </tr>
    </table>
    @if($tailoring->measurement_note)
    <div style="padding:2mm 4mm; border-top:1px solid #f3f4f6;">
        <div class="note-box">📝 {{ $tailoring->measurement_note }}</div>
    </div>
    @endif
    @endif

    {{-- BOTTOM: PAYMENT + SIGNATURE --}}
    <table class="bottom-table">
        <tr>
            <td class="b-left">
                <div style="font-size:8pt;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2mm;">Payment Summary</div>

                <div style="overflow:hidden; margin-bottom:1.5mm;">
                    <span class="pay-lbl">Total Price</span>
                    <span class="pay-val">৳{{ number_format($tailoring->price, 0) }}</span>
                </div>
                <div style="overflow:hidden; margin-bottom:1.5mm;">
                    <span class="pay-lbl">Advance Received</span>
                    <span class="pay-val" style="color:#16a34a;">৳{{ number_format($tailoring->advance_paid, 0) }}</span>
                </div>
                <hr class="pay-divider">
                <div style="overflow:hidden;">
                    <span class="pay-total-lbl">Due Amount</span>
                    <span class="pay-total-val">৳{{ number_format($tailoring->due_amount, 0) }}</span>
                </div>
            </td>
            <td class="b-right">
                <div style="font-size:8pt;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2mm;">Authorized By</div>
                <div class="sig">Signature ________________________</div>
                <div style="margin-top:3mm;" class="sig">Date ____________________________</div>
            </td>
        </tr>
    </table>

</div>

@if($copy === 1)
<div class="cut-line">✂ &nbsp;&nbsp;&nbsp; cut here &nbsp;&nbsp;&nbsp; ✂</div>
@endif

@endfor

</body>
</html>
