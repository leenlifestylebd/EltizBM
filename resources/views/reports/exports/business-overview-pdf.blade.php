@extends('reports.exports._layout')
@section('content')
@php
  $totRev = collect($months)->sum('revenue');
  $totExp = collect($months)->sum('expense');
  $totPro = collect($months)->sum('profit');
  $totOrd = collect($months)->sum('orders');
  $best   = collect($months)->sortByDesc('revenue')->first();
@endphp
<div class="header">
  <div class="header-left">
    <h1>Business Overview &mdash; {{ $year }}</h1>
    <p>Annual performance summary (January &mdash; December {{ $year }})</p>
  </div>
  <div class="header-right">
    <div class="biz">{{ $biz->name ?? 'Business' }}</div>
    <p>{{ now()->format('d M Y') }}</p>
  </div>
</div>

<div class="summary">
  <div class="summary-card blue"><div class="lbl">Total Revenue</div><div class="val">&#2547;{{ number_format($totRev) }}</div></div>
  <div class="summary-card red"><div class="lbl">Total Expenses</div><div class="val">&#2547;{{ number_format($totExp) }}</div></div>
  <div class="summary-card {{ $totPro >= 0 ? 'green' : 'red' }}"><div class="lbl">Net Profit</div><div class="val">&#2547;{{ number_format(abs($totPro)) }}</div></div>
  <div class="summary-card"><div class="lbl">Total Orders</div><div class="val">{{ number_format($totOrd) }}</div></div>
  @if($best)
  <div class="summary-card blue"><div class="lbl">Best Month</div><div class="val" style="font-size:12px;">{{ $best['month'] }}</div></div>
  @endif
</div>

<table>
  <thead>
    <tr>
      <th>Month</th>
      <th class="right">Revenue</th>
      <th class="right">Expenses</th>
      <th class="right">Net Profit</th>
      <th class="center">Orders</th>
      <th class="right">Margin</th>
    </tr>
  </thead>
  <tbody>
    @foreach($months as $m)
    @php $margin = $m['revenue'] > 0 ? round(($m['profit']/$m['revenue'])*100, 1) : 0; @endphp
    <tr>
      <td class="bold">{{ $m['month'] }}</td>
      <td class="right" style="color:#1d4ed8;">{{ $m['revenue'] > 0 ? '&#2547;'.number_format($m['revenue']) : '—' }}</td>
      <td class="right" style="color:#dc2626;">{{ $m['expense'] > 0 ? '&#2547;'.number_format($m['expense']) : '—' }}</td>
      <td class="right bold" style="color:{{ $m['profit'] >= 0 ? '#16a34a' : '#dc2626' }}">
        {{ $m['profit'] != 0 ? ($m['profit'] < 0 ? '&minus;' : '').'&#2547;'.number_format(abs($m['profit'])) : '—' }}
      </td>
      <td class="center">{{ $m['orders'] > 0 ? $m['orders'] : '—' }}</td>
      <td class="right">
        @if($m['revenue'] > 0)
        <span class="badge {{ $margin >= 0 ? 'badge-green' : 'badge-red' }}">{{ $margin }}%</span>
        @else —
        @endif
      </td>
    </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td>TOTAL {{ $year }}</td>
      <td class="right">&#2547;{{ number_format($totRev) }}</td>
      <td class="right">&#2547;{{ number_format($totExp) }}</td>
      <td class="right">{{ $totPro < 0 ? '&minus;' : '' }}&#2547;{{ number_format(abs($totPro)) }}</td>
      <td class="right">{{ number_format($totOrd) }}</td>
      <td class="right">{{ $totRev > 0 ? round(($totPro/$totRev)*100,1) : 0 }}%</td>
    </tr>
  </tfoot>
</table>
@endsection
