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

  {{-- Per-employee breakdown --}}
  @foreach($summaryByEmployee as $summary)
  @php $emp = $summary['employee']; @endphp
  <div style="margin-bottom: 20px;">
    {{-- Employee name bar --}}
    <div style="background:{{ $p800 }}; color:#fff; padding:8px 12px; border-radius:4px 4px 0 0; margin-bottom:0;">
      <span style="font-size:12px; font-weight:700;">{{ $emp?->name ?? 'Unknown Employee' }}</span>
      <span style="font-size:10px; margin-left:12px; opacity:0.8;">{{ $emp?->designation ?? '' }}</span>
      <span style="float:right; font-size:12px; font-weight:700;">&#2547;{{ number_format($summary['total_amount']) }} &nbsp;|&nbsp; {{ number_format($summary['total_qty']) }} pcs</span>
    </div>
    <table style="border-radius: 0 0 4px 4px; overflow:hidden;">
      <thead>
        <tr>
          <th>Product</th>
          <th class="center">Color</th>
          <th class="center">Size</th>
          <th class="center">Quantity</th>
          <th class="center">Entries</th>
          <th class="right">Earnings (&#2547;)</th>
        </tr>
      </thead>
      <tbody>
        @foreach($summary['products'] as $prod)
        <tr>
          <td class="bold">{{ $prod['product_name'] }}</td>
          <td class="center">{{ $prod['color'] }}</td>
          <td class="center">{{ $prod['size'] }}</td>
          <td class="center bold">{{ number_format($prod['quantity']) }}</td>
          <td class="center">{{ $prod['entry_count'] }}</td>
          <td class="right bold" style="color:{{ $pc }};">{{ number_format($prod['amount']) }}</td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="3">Total</td>
          <td class="center">{{ number_format($summary['total_qty']) }}</td>
          <td class="center">{{ $summary['products']->sum('entry_count') }}</td>
          <td class="right">{{ number_format($summary['total_amount']) }}</td>
        </tr>
      </tfoot>
    </table>
  </div>
  @endforeach

  {{-- Grand total --}}
  <div style="background:#1e293b; color:#fff; padding:12px 16px; border-radius:6px; display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
    <div>
      <div style="font-size:12px; font-weight:700;">Grand Total — All Employees</div>
      <div style="font-size:10px; opacity:0.7; margin-top:2px;">{{ $summaryByEmployee->count() }} employees · {{ number_format($totalQty) }} pieces</div>
    </div>
    <div style="font-size:18px; font-weight:700;">&#2547;{{ number_format($totalAmount) }}</div>
  </div>

  {{-- Footer --}}
  <div class="footer">
    Employee Production Report &bull; {{ \Carbon\Carbon::parse($from)->format('d M Y') }} to {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
  </div>
</div>
@endsection
