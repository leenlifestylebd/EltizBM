@extends('reports.exports._layout')
@section('content')
@php
  $totalStock = $products->sum('stock');
  $totalValue = $products->sum(fn($p) => $p->stock * $p->cost_price);
  $outOfStock = $products->where('stock', '<=', 0)->count();
  $lowStock   = $products->filter(fn($p) => $p->stock > 0 && $p->stock <= ($p->low_stock_alert ?? 5))->count();
@endphp
<div class="header">
  <div class="header-left">
    <h1>Stock &amp; Inventory Report</h1>
    <p>Generated {{ now()->format('d M Y') }}</p>
  </div>
  <div class="header-right">
    <div class="biz">{{ $biz->name ?? 'Business' }}</div>
  </div>
</div>
<div class="summary">
  <div class="summary-card blue"><div class="lbl">Total Products</div><div class="val">{{ $products->count() }}</div></div>
  <div class="summary-card"><div class="lbl">Total Units</div><div class="val">{{ number_format($totalStock) }}</div></div>
  <div class="summary-card green"><div class="lbl">Stock Value</div><div class="val">&#2547;{{ number_format($totalValue) }}</div></div>
  <div class="summary-card red"><div class="lbl">Out of Stock</div><div class="val">{{ $outOfStock }}</div></div>
  <div class="summary-card"><div class="lbl">Low Stock</div><div class="val">{{ $lowStock }}</div></div>
</div>
<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Product</th>
      <th>Category</th>
      <th class="center">Stock</th>
      <th class="right">Cost</th>
      <th class="right">Price</th>
      <th class="right">Value</th>
      <th class="center">Status</th>
    </tr>
  </thead>
  <tbody>
    @foreach($products as $i => $p)
    @php
      $status = $p->stock <= 0 ? 'Out' : ($p->stock <= ($p->low_stock_alert ?? 5) ? 'Low' : 'OK');
      $badge  = $p->stock <= 0 ? 'badge-red' : ($p->stock <= ($p->low_stock_alert ?? 5) ? 'badge-yellow' : 'badge-green');
    @endphp
    <tr>
      <td style="color:#94a3b8;">{{ $i+1 }}</td>
      <td class="bold">{{ $p->name }}</td>
      <td style="color:#64748b;">{{ $p->category?->name ?? '—' }}</td>
      <td class="center bold">{{ $p->stock }}</td>
      <td class="right">&#2547;{{ number_format($p->cost_price) }}</td>
      <td class="right">&#2547;{{ number_format($p->price) }}</td>
      <td class="right">&#2547;{{ number_format($p->stock * $p->cost_price) }}</td>
      <td class="center"><span class="badge {{ $badge }}">{{ $status }}</span></td>
    </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="3">TOTAL</td>
      <td class="right">{{ number_format($totalStock) }}</td>
      <td colspan="2"></td>
      <td class="right">&#2547;{{ number_format($totalValue) }}</td>
      <td></td>
    </tr>
  </tfoot>
</table>
@endsection
