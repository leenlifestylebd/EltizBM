@extends('reports.exports._layout')
@section('content')
@php $colors = ['#14b8a6','#22c55e','#3b82f6','#8b5cf6','#f97316','#ec4899','#eab308','#ef4444','#6366f1','#f59e0b']; @endphp
<div class="header">
  <div class="header-left">
    <h1>Income Sources</h1>
    <p>{{ \Carbon\Carbon::parse($from)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
  </div>
  <div class="header-right">
    <div class="biz">{{ $biz->name ?? 'Business' }}</div>
    <p>{{ now()->format('d M Y') }}</p>
  </div>
</div>

<div class="summary">
  <div class="summary-card" style="border-color:#0d9488;">
    <div class="lbl">Total Income</div>
    <div class="val" style="color:#0d9488;">&#2547;{{ number_format($totalIncome) }}</div>
  </div>
  <div class="summary-card">
    <div class="lbl">Sources</div>
    <div class="val">{{ $breakdown->count() }}</div>
  </div>
  <div class="summary-card">
    <div class="lbl">Transactions</div>
    <div class="val">{{ $breakdown->sum('count') }}</div>
  </div>
  <div class="summary-card">
    <div class="lbl">Avg per Entry</div>
    <div class="val">&#2547;{{ $breakdown->sum('count') > 0 ? number_format($totalIncome / $breakdown->sum('count')) : 0 }}</div>
  </div>
</div>

<table>
  <thead>
    <tr>
      <th style="width:5%">#</th>
      <th>Source / Category</th>
      <th class="center" style="width:12%">Transactions</th>
      <th class="right" style="width:18%">Amount (BDT)</th>
      <th class="right" style="width:12%">% Share</th>
      <th class="right" style="width:16%">Avg per tx</th>
    </tr>
  </thead>
  <tbody>
    @foreach($breakdown as $i => $e)
    @php $pct = $totalIncome > 0 ? ($e->total/$totalIncome)*100 : 0; @endphp
    <tr>
      <td style="color:#94a3b8;">{{ $i+1 }}</td>
      <td>
        <span style="display:inline-block; width:8px; height:8px; background:{{ $colors[$i % count($colors)] }}; border-radius:50%; margin-right:4px;"></span>
        <strong>{{ $e->category?->name ?? 'Uncategorized' }}</strong>
        <div class="bar-bg"><div class="bar-fill" style="width:{{ min(100,$pct) }}%; background:{{ $colors[$i % count($colors)] }};"></div></div>
      </td>
      <td class="center">{{ $e->count }}</td>
      <td class="right" style="color:#0d9488; font-weight:700;">&#2547;{{ number_format($e->total) }}</td>
      <td class="right"><span class="badge badge-green">{{ number_format($pct, 1) }}%</span></td>
      <td class="right" style="color:#64748b;">&#2547;{{ $e->count > 0 ? number_format($e->total/$e->count) : 0 }}</td>
    </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="2">Total</td>
      <td class="right">{{ $breakdown->sum('count') }}</td>
      <td class="right">&#2547;{{ number_format($totalIncome) }}</td>
      <td class="right">100%</td>
      <td></td>
    </tr>
  </tfoot>
</table>
@endsection
