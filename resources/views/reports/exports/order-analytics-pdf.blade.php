@extends('reports.exports._layout')
@section('content')
<div class="header">
  <div class="header-left">
    <h1>Order Analytics</h1>
    <p>{{ \Carbon\Carbon::parse($from)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
  </div>
  <div class="header-right">
    <div class="biz">{{ $biz->name ?? 'Business' }}</div>
    <p>{{ now()->format('d M Y') }}</p>
  </div>
</div>
<div class="summary">
  <div class="summary-card"><div class="lbl">Total Orders</div><div class="val">{{ $stats['total'] }}</div></div>
  <div class="summary-card blue"><div class="lbl">Revenue</div><div class="val">&#2547;{{ number_format($stats['revenue']) }}</div></div>
  <div class="summary-card green"><div class="lbl">Completed</div><div class="val">{{ $stats['completed'] }}</div></div>
  <div class="summary-card red"><div class="lbl">Cancelled</div><div class="val">{{ $stats['cancelled'] }}</div></div>
</div>
<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Order #</th>
      <th>Customer</th>
      <th>Date</th>
      <th class="center">Status</th>
      <th>Payment</th>
      <th class="right">Amount</th>
    </tr>
  </thead>
  <tbody>
    @foreach($orders as $i => $o)
    @php
      $badge = match($o->status) {
        'completed','delivered' => 'badge-green',
        'cancelled','returned'  => 'badge-red',
        'pending'               => 'badge-yellow',
        default                 => 'badge-blue',
      };
    @endphp
    <tr>
      <td style="color:#94a3b8;">{{ $i+1 }}</td>
      <td class="bold">#{{ $o->order_number ?? $o->id }}</td>
      <td>{{ $o->customer?->name ?? '—' }}</td>
      <td>{{ \Carbon\Carbon::parse($o->created_at)->format('d M Y') }}</td>
      <td class="center"><span class="badge {{ $badge }}">{{ ucfirst($o->status) }}</span></td>
      <td style="color:#64748b;">{{ ucfirst($o->payment_method ?? '—') }}</td>
      <td class="right bold">&#2547;{{ number_format($o->total) }}</td>
    </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="5">TOTAL ({{ $orders->count() }} orders)</td>
      <td></td>
      <td class="right">&#2547;{{ number_format($orders->sum('total')) }}</td>
    </tr>
  </tfoot>
</table>
@endsection
