@php
    $pc   = isset($biz) ? $biz->themeColor('600') : '#2563eb';
    $p50  = isset($biz) ? $biz->themeColor('50')  : '#eff6ff';
    $p100 = isset($biz) ? $biz->themeColor('100') : '#dbeafe';
    $p800 = isset($biz) ? $biz->themeColor('800') : '#1e40af';
@endphp
@extends('reports.exports._layout')
@section('content')
@php
  $grossMargin = $revenue > 0 ? round(($grossProfit/$revenue)*100, 1) : 0;
  $netMargin   = $revenue > 0 ? round(($netProfit/$revenue)*100, 1) : 0;
  $totalExp    = $expenses + $productionWages;
@endphp

<div class="header">
  <div class="header-left">
    <h1>Profit &amp; Loss Statement</h1>
    <p>{{ \Carbon\Carbon::parse($from)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
  </div>
  <div class="header-right">
    <div class="biz">{{ $biz->name ?? 'Business' }}</div>
    <p>{{ now()->format('d M Y') }}</p>
  </div>
</div>

{{-- Summary Cards --}}
<div class="summary">
  <div class="summary-card blue">
    <div class="lbl">Revenue</div>
    <div class="val">&#2547;{{ number_format($revenue) }}</div>
  </div>
  <div class="summary-card red">
    <div class="lbl">Total Expenses</div>
    <div class="val">&#2547;{{ number_format($totalExp) }}</div>
  </div>
  <div class="summary-card {{ $grossProfit >= 0 ? 'green' : 'red' }}">
    <div class="lbl">Gross Profit</div>
    <div class="val">&#2547;{{ number_format($grossProfit) }}</div>
  </div>
  <div class="summary-card {{ $netProfit >= 0 ? 'green' : 'red' }}">
    <div class="lbl">Net Profit</div>
    <div class="val">&#2547;{{ number_format($netProfit) }}</div>
  </div>
</div>

{{-- P&L Statement --}}
<table>
  <thead>
    <tr>
      <th style="width:60%">Item</th>
      <th class="right">Amount (BDT)</th>
      <th class="right" style="width:15%">% of Revenue</th>
    </tr>
  </thead>
  <tbody>
    <tr><td colspan="3" class="section-header">INCOME</td></tr>
    <tr>
      <td class="bold">Revenue from Sales</td>
      <td class="right bold">&#2547;{{ number_format($revenue) }}</td>
      <td class="right">100%</td>
    </tr>
    <tr>
      <td style="padding-left:24px; color:#64748b;">Cost of Goods Sold (COGS)</td>
      <td class="right" style="color:#dc2626;">&minus;&#2547;{{ number_format($cogs) }}</td>
      <td class="right" style="color:#64748b;">{{ $revenue > 0 ? round(($cogs/$revenue)*100,1) : 0 }}%</td>
    </tr>
    <tr style="background:#f0fdf4;">
      <td class="bold">Gross Profit</td>
      <td class="right bold" style="color:{{ $grossProfit >= 0 ? '#16a34a' : '#dc2626' }}">{{ $grossProfit >= 0 ? '' : '&minus;' }}&#2547;{{ number_format(abs($grossProfit)) }}</td>
      <td class="right bold" style="color:{{ $grossProfit >= 0 ? '#16a34a' : '#dc2626' }}">{{ $grossMargin }}%</td>
    </tr>

    <tr><td colspan="3" class="section-header">OPERATING ADJUSTMENTS</td></tr>
    <tr>
      <td style="padding-left:24px; color:#64748b;">Other Income</td>
      <td class="right" style="color:#0d9488;">+&#2547;{{ number_format($otherIncome) }}</td>
      <td class="right" style="color:#64748b;">{{ $revenue > 0 ? round(($otherIncome/$revenue)*100,1) : 0 }}%</td>
    </tr>
    <tr>
      <td style="padding-left:24px; color:#64748b;">Operating Expenses</td>
      <td class="right" style="color:#dc2626;">&minus;&#2547;{{ number_format($expenses) }}</td>
      <td class="right" style="color:#64748b;">{{ $revenue > 0 ? round(($expenses/$revenue)*100,1) : 0 }}%</td>
    </tr>
    <tr>
      <td style="padding-left:24px; color:#64748b;">Production Wages</td>
      <td class="right" style="color:#7c3aed;">&minus;&#2547;{{ number_format($productionWages) }}</td>
      <td class="right" style="color:#64748b;">{{ $revenue > 0 ? round(($productionWages/$revenue)*100,1) : 0 }}%</td>
    </tr>

    <tr style="background:{{ $p50 }}; border-top: 2px solid {{ $p100 }};">
      <td class="bold" style="font-size:12px;">Net Profit / Loss</td>
      <td class="right bold" style="font-size:13px; color:{{ $netProfit >= 0 ? '{{ $p800 }}' : '#dc2626' }}">{{ $netProfit >= 0 ? '' : '&minus;' }}&#2547;{{ number_format(abs($netProfit)) }}</td>
      <td class="right bold" style="color:{{ $netProfit >= 0 ? '{{ $p800 }}' : '#dc2626' }}">{{ $netMargin }}%</td>
    </tr>
  </tbody>
</table>

{{-- Expense Breakdown --}}
@if($expenseBreakdown->isNotEmpty())
<div style="margin-top: 20px;">
  <div style="font-size:11px; font-weight:700; color:#1e293b; margin-bottom:8px;">Expense Breakdown by Category</div>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Category</th>
        <th class="center">Transactions</th>
        <th class="right">Amount</th>
        <th class="right">% Share</th>
      </tr>
    </thead>
    <tbody>
      @php $totalExp2 = $expenseBreakdown->sum('total'); @endphp
      @foreach($expenseBreakdown as $i => $e)
      <tr>
        <td style="color:#94a3b8;">{{ $i+1 }}</td>
        <td class="bold">{{ $e->category?->name ?? 'Uncategorized' }}</td>
        <td class="center">{{ $e->count }}</td>
        <td class="right" style="color:#dc2626;">&#2547;{{ number_format($e->total) }}</td>
        <td class="right">{{ $totalExp2 > 0 ? number_format(($e->total/$totalExp2)*100,1) : 0 }}%</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3">Total</td>
        <td class="right">&#2547;{{ number_format($totalExp2) }}</td>
        <td class="right">100%</td>
      </tr>
    </tfoot>
  </table>
</div>
@endif
@endsection
