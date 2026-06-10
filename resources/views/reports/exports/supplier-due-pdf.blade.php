@extends('reports.exports._layout')
@section('content')
<div class="header">
  <div class="header-left">
    <h1>Supplier Due Report</h1>
    <p>Outstanding payments to vendors — {{ now()->format('d M Y') }}</p>
  </div>
  <div class="header-right">
    <div class="biz">{{ $biz->name ?? 'Business' }}</div>
  </div>
</div>
<div class="summary">
  <div class="summary-card red"><div class="lbl">Total Due</div><div class="val">&#2547;{{ number_format($totalDue) }}</div></div>
  <div class="summary-card"><div class="lbl">Suppliers</div><div class="val">{{ $vendors->count() }}</div></div>
  <div class="summary-card"><div class="lbl">Total POs</div><div class="val">{{ $vendors->sum('po_count') }}</div></div>
</div>
<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Vendor / Supplier</th>
      <th class="center">PO Count</th>
      <th class="right">Total Ordered</th>
      <th class="right">Due Amount</th>
    </tr>
  </thead>
  <tbody>
    @foreach($vendors as $i => $v)
    <tr>
      <td style="color:#94a3b8;">{{ $i+1 }}</td>
      <td class="bold">{{ $v->vendor?->name ?? '—' }}</td>
      <td class="center">{{ $v->po_count }}</td>
      <td class="right">&#2547;{{ number_format($v->total_ordered) }}</td>
      <td class="right bold" style="color:#dc2626;">&#2547;{{ number_format($v->due_amount) }}</td>
    </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="2">TOTAL</td>
      <td class="right">{{ $vendors->sum('po_count') }}</td>
      <td></td>
      <td class="right">&#2547;{{ number_format($totalDue) }}</td>
    </tr>
  </tfoot>
</table>
@endsection
