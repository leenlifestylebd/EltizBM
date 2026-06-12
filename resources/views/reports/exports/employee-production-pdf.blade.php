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

  {{-- Summary --}}
  <div class="summary">
    <div class="summary-card blue">
      <div class="lbl">Total Wages</div>
      <div class="val">&#2547;{{ number_format($totalAmount) }}</div>
    </div>
    <div class="summary-card">
      <div class="lbl">Total Pieces</div>
      <div class="val">{{ number_format($totalQty) }}</div>
    </div>
    <div class="summary-card">
      <div class="lbl">Employees</div>
      <div class="val">{{ $summaryByEmployee->count() }}</div>
    </div>
    <div class="summary-card">
      <div class="lbl">Avg Rate/pc</div>
      <div class="val">&#2547;{{ $totalQty > 0 ? number_format($totalAmount / $totalQty, 2) : '0.00' }}</div>
    </div>
  </div>

  {{-- Per-employee entries --}}
  @foreach($summaryByEmployee as $summary)
  @php $emp = $summary['employee']; @endphp
  <div style="margin-bottom: 20px;">

    {{-- Employee header bar --}}
    <table style="margin-bottom:0; border-radius:4px 4px 0 0; overflow:hidden;">
      <thead>
        <tr>
          <th style="text-align:left; font-size:12px; background:{{ $p800 }};">
            {{ $emp?->name ?? 'Unknown Employee' }}
            @if($emp?->designation)
              <span style="font-weight:400; opacity:0.8; font-size:10px; margin-left:8px;">{{ $emp->designation }}</span>
            @endif
          </th>
          <th class="center" style="background:{{ $p800 }}; white-space:nowrap; font-size:10px; font-weight:400; opacity:0.9;">
            {{ number_format($summary['total_qty']) }} pcs &nbsp;&bull;&nbsp; {{ $summary['entry_count'] }} entries
          </th>
          <th class="right" style="background:{{ $p800 }}; white-space:nowrap; font-size:12px;">
            &#2547;{{ number_format($summary['total_amount']) }}
          </th>
        </tr>
      </thead>
    </table>

    {{-- All entries table --}}
    <table style="margin-top:0; border-radius:0 0 4px 4px; overflow:hidden;">
      <thead>
        <tr style="background:#475569;">
          <th style="background:#475569; width:15%;">Date</th>
          <th style="background:#475569; width:42%;">Product</th>
          <th class="center" style="background:#475569; width:13%;">Color</th>
          <th class="center" style="background:#475569; width:10%;">Size</th>
          <th class="center" style="background:#475569; width:10%;">Qty</th>
          <th class="right" style="background:#475569; width:10%;">Amount (&#2547;)</th>
        </tr>
      </thead>
      <tbody>
        @foreach($summary['entries'] as $entry)
        @php
            $entryQty    = (int)   ($entry->quantity ?? 0);
            $entryAmount = (float) ($entry->amount   ?? 0);
            $productName = $entry->productionRate?->product_name ?? 'Manual Entry';
            $entryColor  = $entry->productionRate?->color ?? '—';
            $entrySize   = $entry->productionRate?->size  ?? '—';
        @endphp
        <tr>
          <td style="white-space:nowrap;">
            {{ $entry->entry_date ? $entry->entry_date->format('d M Y') : '—' }}
          </td>
          <td>
            <div style="font-weight:600; font-size:10px;">{{ $productName }}</div>
            @if($entry->notes)
            <div style="font-size:9px; color:#64748b; margin-top:1px;">{{ $entry->notes }}</div>
            @endif
          </td>
          <td class="center">{{ $entryColor }}</td>
          <td class="center">{{ $entrySize }}</td>
          <td class="center bold">{{ number_format($entryQty) }}</td>
          <td class="right bold" style="color:{{ $pc }};">{{ number_format($entryAmount) }}</td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" style="font-weight:700;">Total</td>
          <td class="center" style="font-weight:700;">{{ number_format($summary['total_qty']) }}</td>
          <td class="right" style="font-weight:700;">&#2547;{{ number_format($summary['total_amount']) }}</td>
        </tr>
      </tfoot>
    </table>
  </div>
  @endforeach

  {{-- Grand total --}}
  <table style="margin-top:8px;">
    <tfoot>
      <tr>
        <td style="font-size:12px;">Grand Total &mdash; All Employees</td>
        <td class="center">{{ $summaryByEmployee->count() }} employees</td>
        <td class="center">{{ number_format($totalQty) }} pcs</td>
        <td class="right" style="font-size:14px;">&#2547;{{ number_format($totalAmount) }}</td>
      </tr>
    </tfoot>
  </table>

</div>
@endsection
