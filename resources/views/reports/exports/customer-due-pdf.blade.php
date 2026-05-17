@extends('reports.exports._layout')
@section('content')
<div class="header">
  <div class="header-left">
    <h1>Customer Due Report</h1>
    <p>Customers with outstanding payments — {{ now()->format('d M Y') }}</p>
  </div>
  <div class="header-right">
    <div class="biz">{{ $biz->name ?? 'Business' }}</div>
  </div>
</div>
<div class="summary">
  <div class="summary-card red"><div class="lbl">Total Due</div><div class="val">&#2547;{{ number_format($totalDue) }}</div></div>
  <div class="summary-card"><div class="lbl">Customers</div><div class="val">{{ $customers->count() }}</div></div>
  <div class="summary-card"><div class="lbl">Avg Due</div><div class="val">&#2547;{{ $customers->count() > 0 ? number_format($totalDue / $customers->count()) : 0 }}</div></div>
</div>
<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Customer</th>
      <th>Phone</th>
      <th class="right">Total Purchase</th>
      <th class="right">Due Amount</th>
      <th class="right">Due %</th>
    </tr>
  </thead>
  <tbody>
    @foreach($customers as $i => $c)
    @php $duePct = $c->total_purchase > 0 ? round(($c->due_amount/$c->total_purchase)*100, 1) : 100; @endphp
    <tr>
      <td style="color:#94a3b8;">{{ $i+1 }}</td>
      <td class="bold">{{ $c->name }}</td>
      <td style="color:#64748b;">{{ $c->phone ?? '—' }}</td>
      <td class="right">&#2547;{{ number_format($c->total_purchase) }}</td>
      <td class="right bold" style="color:#dc2626;">&#2547;{{ number_format($c->due_amount) }}</td>
      <td class="right"><span class="badge badge-red">{{ $duePct }}%</span></td>
    </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="3">TOTAL</td>
      <td class="right">&#2547;{{ number_format($customers->sum('total_purchase')) }}</td>
      <td class="right">&#2547;{{ number_format($totalDue) }}</td>
      <td></td>
    </tr>
  </tfoot>
</table>
@endsection
