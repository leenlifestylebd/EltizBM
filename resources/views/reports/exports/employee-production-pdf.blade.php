@php
    $pc   = isset($biz) ? $biz->themeColor('600') : '#2563eb';
    $p50  = isset($biz) ? $biz->themeColor('50')  : '#eff6ff';
    $p100 = isset($biz) ? $biz->themeColor('100') : '#dbeafe';
    $p800 = isset($biz) ? $biz->themeColor('800') : '#1e40af';
@endphp
@extends('reports.exports._layout')
@section('content')
<div class="page">

  {{-- Header --}}
  <div class="header">
    <div class="header-left">
      <h1>Employee Production Report</h1>
      <p>{{ \Carbon\Carbon::parse($from)->format('d M Y') }} &ndash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
    </div>
    <div class="header-right">
      <div class="biz">{{ $biz->name ?? 'Business' }}</div>
      <p>Generated: {{ now()->format('d M Y, H:i') }}</p>
    </div>
  </div>

  {{-- Summary strip --}}
  <div class="summary">
    <div class="summary-card blue">
      <div class="lbl">Total Wages</div>
      <div class="val">&#2547;{{ number_format($totalAmount) }}</div>
    </div>
    <div class="summary-card">
      <div class="lbl">Total Pieces</div>
      <div class="val">{{ number_format($totalQty) }}</div>
    </div>
    <div class="summary-card green">
      <div class="lbl">Total Paid</div>
      <div class="val">&#2547;{{ number_format($totalPaid ?? 0) }}</div>
    </div>
    @php $tb = $totalBalance ?? ($totalAmount - ($totalPaid ?? 0)); @endphp
    <div class="summary-card {{ $tb > 0 ? 'red' : ($tb < 0 ? 'purple' : '') }}">
      <div class="lbl">{{ $tb >= 0 ? 'Total Due' : 'Overpaid' }}</div>
      <div class="val">&#2547;{{ number_format(abs($tb)) }}</div>
    </div>
  </div>

  {{-- Per-employee section --}}
  @foreach($summaryByEmployee as $summary)
  @php
      $emp     = $summary['employee'];
      $earned  = (float) $summary['total_amount'];
      $paid    = (float) ($summary['total_paid']  ?? 0);
      $balance = (float) ($summary['balance']     ?? $earned - $paid);
      $isDue   = $balance > 0;
      $isOver  = $balance < 0;
  @endphp
  <div style="margin-bottom: 22px;">

    {{-- Employee name bar --}}
    <table style="margin-bottom:0; border-radius:4px 4px 0 0; overflow:hidden;">
      <thead>
        <tr>
          <th style="text-align:left; font-size:12px; background:{{ $p800 }}; width:50%;">
            {{ $emp?->name ?? 'Unknown Employee' }}
            @if($emp?->designation)
              <span style="font-weight:400; opacity:0.8; font-size:10px; margin-left:8px;">{{ $emp->designation }}</span>
            @endif
          </th>
          <th class="center" style="background:{{ $p800 }}; font-size:10px; font-weight:400; opacity:0.85; width:20%;">
            {{ number_format($summary['total_qty']) }} pcs &nbsp;&bull;&nbsp; {{ $summary['entry_count'] }} entries
          </th>
          <th class="right" style="background:{{ $p800 }}; font-size:12px; width:30%;">
            Earned: &#2547;{{ number_format($earned) }}
          </th>
        </tr>
      </thead>
    </table>

    {{-- All entries --}}
    <table style="margin-top:0; overflow:hidden;">
      <thead>
        <tr style="background:#475569;">
          <th style="background:#475569; width:14%;">Date</th>
          <th style="background:#475569; width:43%;">Product</th>
          <th class="center" style="background:#475569; width:12%;">Color</th>
          <th class="center" style="background:#475569; width:9%;">Size</th>
          <th class="center" style="background:#475569; width:9%;">Qty</th>
          <th class="right" style="background:#475569; width:13%;">Amount (&#2547;)</th>
        </tr>
      </thead>
      <tbody>
        @foreach($summary['entries'] as $entry)
        @php
            $eQty    = (int)   ($entry->quantity ?? 0);
            $eAmt    = (float) ($entry->amount   ?? 0);
            $eName   = $entry->productionRate?->product_name ?? 'Manual Entry';
            $eColor  = $entry->productionRate?->color ?? '—';
            $eSize   = $entry->productionRate?->size  ?? '—';
        @endphp
        <tr>
          <td style="white-space:nowrap; font-size:9px;">
            {{ $entry->entry_date ? $entry->entry_date->format('d M Y') : '—' }}
          </td>
          <td>
            <div style="font-weight:600; font-size:10px;">{{ $eName }}</div>
            @if($entry->notes)
            <div style="font-size:9px; color:#64748b; margin-top:1px;">{{ $entry->notes }}</div>
            @endif
          </td>
          <td class="center">{{ $eColor }}</td>
          <td class="center">{{ $eSize }}</td>
          <td class="center bold">{{ number_format($eQty) }}</td>
          <td class="right bold" style="color:{{ $pc }};">{{ number_format($eAmt) }}</td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        {{-- Subtotal row --}}
        <tr>
          <td colspan="4" style="font-weight:700; font-size:10px;">Subtotal</td>
          <td class="center" style="font-weight:700;">{{ number_format($summary['total_qty']) }}</td>
          <td class="right" style="font-weight:700;">&#2547;{{ number_format($earned) }}</td>
        </tr>
      </tfoot>
    </table>

    {{-- Ledger summary for this employee --}}
    <table style="margin-top:4px; border-radius:0 0 4px 4px; overflow:hidden;">
      <tbody>
        <tr style="background:#f1f5f9;">
          <td style="width:60%; font-size:10px; color:#475569; padding:5px 10px;">Paid this period</td>
          <td class="right" style="width:40%; font-size:10px; font-weight:700; color:#16a34a; padding:5px 10px;">
            &#2547;{{ number_format($paid) }}
          </td>
        </tr>
        <tr style="background:{{ $isDue ? '#fef2f2' : ($isOver ? '#f5f3ff' : '#f0fdf4') }};">
          <td style="font-size:10px; font-weight:700; padding:5px 10px;
                     color:{{ $isDue ? '#991b1b' : ($isOver ? '#5b21b6' : '#166534') }};">
            @if($isDue)
              Due to worker
            @elseif($isOver)
              Overpaid (credit)
            @else
              Settled
            @endif
          </td>
          <td class="right" style="font-size:11px; font-weight:700; padding:5px 10px;
                     color:{{ $isDue ? '#dc2626' : ($isOver ? '#7c3aed' : '#16a34a') }};">
            @if($isOver)&minus;@endif&#2547;{{ number_format(abs($balance)) }}
          </td>
        </tr>
      </tbody>
    </table>

  </div>
  @endforeach

  {{-- Grand total --}}
  @php
      $gtb = $totalBalance ?? ($totalAmount - ($totalPaid ?? 0));
  @endphp
  <table style="margin-top:8px;">
    <tfoot>
      <tr>
        <td style="font-size:11px; font-weight:700; width:34%;">Grand Total — All Employees</td>
        <td class="center" style="width:17%;">{{ number_format($totalQty) }} pcs</td>
        <td class="right" style="width:17%;">Earned: &#2547;{{ number_format($totalAmount) }}</td>
        <td class="right" style="width:17%; color:#16a34a;">Paid: &#2547;{{ number_format($totalPaid ?? 0) }}</td>
        <td class="right" style="width:15%;
             color:{{ $gtb > 0 ? '#dc2626' : ($gtb < 0 ? '#7c3aed' : '#16a34a') }};">
          @if($gtb > 0)Due: &#2547;{{ number_format($gtb) }}@elseif($gtb < 0)Overpaid: &#2547;{{ number_format(abs($gtb)) }}@else Settled @endif
        </td>
      </tr>
    </tfoot>
  </table>

</div>
@endsection
