@extends('reports.exports._layout')
@section('content')
<div class="header">
  <div class="header-left">
    <h1>Expense by Category</h1>
    <p>{{ \Carbon\Carbon::parse($from)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
  </div>
  <div class="header-right">
    <div class="biz">{{ $biz->name ?? 'Business' }}</div>
    <p>{{ now()->format('d M Y') }}</p>
  </div>
</div>

<div class="summary">
  <div class="summary-card red">
    <div class="lbl">Total Expenses</div>
    <div class="val">&#2547;{{ number_format($totalExpense) }}</div>
  </div>
  <div class="summary-card">
    <div class="lbl">Categories</div>
    <div class="val">{{ $breakdown->count() }}</div>
  </div>
  <div class="summary-card">
    <div class="lbl">Transactions</div>
    <div class="val">{{ $breakdown->sum('count') }}</div>
  </div>
  <div class="summary-card">
    <div class="lbl">Avg per Entry</div>
    <div class="val">&#2547;{{ $breakdown->sum('count') > 0 ? number_format($totalExpense / $breakdown->sum('count')) : 0 }}</div>
  </div>
</div>

@php
$colors = ['#ef4444','#f97316','#eab308','#22c55e','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f59e0b','#6366f1'];
@endphp

{{-- Category Summary Table --}}
<table>
  <thead>
    <tr>
      <th style="width:5%">#</th>
      <th>Category</th>
      <th class="center" style="width:12%">Transactions</th>
      <th class="right" style="width:18%">Amount (BDT)</th>
      <th class="right" style="width:12%">% Share</th>
      <th class="right" style="width:16%">Avg per tx</th>
    </tr>
  </thead>
  <tbody>
    @foreach($breakdown as $i => $e)
    @php $pct = $totalExpense > 0 ? ($e->total/$totalExpense)*100 : 0; @endphp
    <tr>
      <td style="color:#94a3b8;">{{ $i+1 }}</td>
      <td>
        <span style="display:inline-block; width:8px; height:8px; background:{{ $colors[$i % count($colors)] }}; border-radius:50%; margin-right:4px;"></span>
        <strong>{{ $e->category?->name ?? 'Uncategorized' }}</strong>
        <div class="bar-bg"><div class="bar-fill" style="width:{{ min(100,$pct) }}%; background:{{ $colors[$i % count($colors)] }};"></div></div>
      </td>
      <td class="center">{{ $e->count }}</td>
      <td class="right" style="color:#dc2626; font-weight:700;">&#2547;{{ number_format($e->total) }}</td>
      <td class="right">
        <span class="badge badge-red">{{ number_format($pct, 1) }}%</span>
      </td>
      <td class="right" style="color:#64748b;">&#2547;{{ $e->count > 0 ? number_format($e->total/$e->count) : 0 }}</td>
    </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="2">Total</td>
      <td class="right">{{ $breakdown->sum('count') }}</td>
      <td class="right">&#2547;{{ number_format($totalExpense) }}</td>
      <td class="right">100%</td>
      <td></td>
    </tr>
  </tfoot>
</table>

{{-- Item-level detail per category --}}
@if(isset($transactions))
<div style="margin-top: 24px;">
  <div style="font-size:12px; font-weight:700; color:#1e293b; margin-bottom:12px; padding-bottom:6px; border-bottom:1px solid #e2e8f0;">Transaction Detail by Category</div>
  @foreach($breakdown as $i => $e)
  @php $catTxns = $transactions->get($e->category_id, collect()); @endphp
  @if($catTxns->isNotEmpty())
  <div style="margin-bottom:16px; page-break-inside:avoid;">
    <div style="background:{{ $colors[$i % count($colors)] }}; color:#fff; padding:6px 10px; border-radius:3px 3px 0 0; font-size:10px; font-weight:700;">
      {{ $e->category?->name ?? 'Uncategorized' }}
      <span style="float:right;">{{ $e->count }} transactions &nbsp;&bull;&nbsp; &#2547;{{ number_format($e->total) }}</span>
    </div>
    <table style="margin-bottom:0;">
      <thead>
        <tr style="background:#f8fafc;">
          <th style="color:#374151; background:#f8fafc;">Date</th>
          <th style="color:#374151; background:#f8fafc;">Title</th>
          <th class="center" style="color:#374151; background:#f8fafc; width:14%;">Method</th>
          <th class="right" style="color:#374151; background:#f8fafc; width:18%;">Amount (&#2547;)</th>
        </tr>
      </thead>
      <tbody>
        @foreach($catTxns as $txn)
        <tr>
          <td style="width:14%;">{{ $txn->transaction_date->format('d M Y') }}</td>
          <td>
            {{ $txn->title }}
            @if($txn->reference) <span style="color:#94a3b8; font-size:9px;">#{{ $txn->reference }}</span> @endif
          </td>
          <td class="center">{{ ucfirst($txn->payment_method ?? '—') }}</td>
          <td class="right" style="font-weight:700; color:#dc2626;">{{ number_format($txn->amount) }}</td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="3" style="background:#fff3f3; color:#b91c1c;">Subtotal</td>
          <td class="right" style="background:#fff3f3; color:#b91c1c;">&#2547;{{ number_format($e->total) }}</td>
        </tr>
      </tfoot>
    </table>
  </div>
  @endif
  @endforeach
</div>
@endif
@endsection
