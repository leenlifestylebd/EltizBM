@extends('reports.exports._layout')
@section('content')
@php
$channelColors = [
    'online'=>'#3b82f6','outlet'=>'#8b5cf6','direct'=>'#10b981',
    'facebook'=>'#1d4ed8','whatsapp'=>'#15803d','phone'=>'#b45309',
    'walk-in'=>'#9d174d','other'=>'#6b7280',
];
$fallback = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ec4899','#ef4444','#14b8a6','#6366f1'];
@endphp
<div class="page">

  {{-- Header --}}
  <div class="header">
    <div class="header-left">
      <h1>Sales by Channel Report</h1>
      <p>{{ \Carbon\Carbon::parse($from)->format('d M Y') }} &ndash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
    </div>
    <div class="header-right">
      <div class="biz">{{ $biz->name ?? 'Business' }}</div>
      <p>Generated: {{ now()->format('d M Y, H:i') }}</p>
    </div>
  </div>

  {{-- Summary --}}
  @php
    $totalOrders = $bySource->sum('total_count');
    $completedOrders = $bySource->sum('completed_count');
  @endphp
  <div class="summary">
    <div class="summary-card purple">
      <div class="lbl">Total Revenue</div>
      <div class="val">&#2547;{{ number_format($totalRevenue) }}</div>
    </div>
    <div class="summary-card">
      <div class="lbl">Total Orders</div>
      <div class="val">{{ number_format($totalOrders) }}</div>
    </div>
    <div class="summary-card green">
      <div class="lbl">Completed</div>
      <div class="val">{{ $completedOrders }}</div>
    </div>
    <div class="summary-card">
      <div class="lbl">Channels</div>
      <div class="val">{{ $bySource->count() }}</div>
    </div>
  </div>

  {{-- Channel Table --}}
  <table>
    <thead>
      <tr>
        <th>Channel</th>
        <th class="center">Orders</th>
        <th class="center">Completed</th>
        <th class="center">Cancelled</th>
        <th class="center">Completion %</th>
        <th class="right">Revenue (&#2547;)</th>
        <th class="right">Share %</th>
      </tr>
    </thead>
    <tbody>
      @foreach($bySource as $i => $ch)
      @php
        $color = $channelColors[$ch->source] ?? $fallback[$i % count($fallback)];
        $completion = $ch->total_count > 0 ? round(($ch->completed_count / $ch->total_count) * 100) : 0;
        $share = $totalRevenue > 0 ? round(($ch->completed_revenue / $totalRevenue) * 100, 1) : 0;
      @endphp
      <tr>
        <td>
          <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:{{ $color }}; margin-right:6px;"></span>
          <strong style="text-transform:capitalize;">{{ $ch->source }}</strong>
        </td>
        <td class="center">{{ $ch->total_count }}</td>
        <td class="center bold" style="color:#16a34a;">{{ $ch->completed_count }}</td>
        <td class="center" style="color:{{ $ch->cancelled_count > 0 ? '#dc2626' : '#6b7280' }};">{{ $ch->cancelled_count }}</td>
        <td class="center">
          <div style="background:#e5e7eb; border-radius:10px; height:8px; width:80px; display:inline-block; vertical-align:middle; overflow:hidden;">
            <div style="background:{{ $color }}; height:100%; width:{{ $completion }}%;"></div>
          </div>
          <span style="margin-left:4px;">{{ $completion }}%</span>
        </td>
        <td class="right bold" style="color:{{ $color }};">{{ number_format($ch->completed_revenue) }}</td>
        <td class="right">
          <span style="background:{{ $color }}22; color:{{ $color }}; padding:2px 6px; border-radius:10px; font-size:9px; font-weight:700;">{{ $share }}%</span>
        </td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td>TOTAL</td>
        <td class="center">{{ $totalOrders }}</td>
        <td class="center">{{ $completedOrders }}</td>
        <td class="center">{{ $bySource->sum('cancelled_count') }}</td>
        <td></td>
        <td class="right">{{ number_format($totalRevenue) }}</td>
        <td class="right">100%</td>
      </tr>
    </tfoot>
  </table>

  {{-- Channel Revenue Bars --}}
  <div style="margin-top:20px;">
    <div style="font-size:12px; font-weight:700; color:#1e293b; margin-bottom:10px;">Revenue Distribution</div>
    @php $maxRev = $bySource->max('completed_revenue') ?: 1; @endphp
    @foreach($bySource as $i => $ch)
    @php
      $color = $channelColors[$ch->source] ?? $fallback[$i % count($fallback)];
      $pct = min(100, ($ch->completed_revenue / $maxRev) * 100);
      $share = $totalRevenue > 0 ? round(($ch->completed_revenue / $totalRevenue) * 100, 1) : 0;
    @endphp
    <div style="margin-bottom:8px;">
      <div style="display:flex; justify-content:space-between; font-size:10px; margin-bottom:3px;">
        <span style="font-weight:600; text-transform:capitalize;">{{ $ch->source }}</span>
        <span style="color:#64748b;">&#2547;{{ number_format($ch->completed_revenue) }} &nbsp;({{ $share }}%)</span>
      </div>
      <div style="background:#f1f5f9; border-radius:4px; height:10px; width:100%; overflow:hidden;">
        <div style="background:{{ $color }}; height:100%; width:{{ $pct }}%; border-radius:4px;"></div>
      </div>
    </div>
    @endforeach
  </div>

  {{-- Footer --}}
  <div class="footer">
    Sales by Channel Report &bull; {{ \Carbon\Carbon::parse($from)->format('d M Y') }} to {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
  </div>
</div>
@endsection
