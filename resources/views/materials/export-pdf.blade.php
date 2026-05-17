<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1a202c; background: #fff; }
    .header { background: #1e3a5f; color: #fff; padding: 16px 20px; margin-bottom: 16px; }
    .header h1 { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
    .header p { font-size: 10px; opacity: 0.8; }
    .header .meta { display: flex; justify-content: space-between; align-items: flex-end; }
    .header .right { text-align: right; font-size: 9px; opacity: 0.75; }

    .summary-row { display: flex; gap: 10px; margin: 0 20px 14px; }
    .summary-card { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; }
    .summary-card .label { font-size: 8px; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
    .summary-card .value { font-size: 14px; font-weight: bold; color: #2d3748; }
    .summary-card .sub { font-size: 8px; color: #a0aec0; margin-top: 2px; }

    .section { margin: 0 20px 16px; }
    .section-title { font-size: 11px; font-weight: bold; color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px; margin-bottom: 8px; }

    table { width: 100%; border-collapse: collapse; font-size: 9px; }
    thead tr { background: #2d3748; color: #fff; }
    thead th { padding: 6px 8px; text-align: left; font-weight: 600; font-size: 8.5px; }
    thead th.right { text-align: right; }
    tbody tr { border-bottom: 1px solid #f0f0f0; }
    tbody tr:nth-child(even) { background: #f7fafc; }
    tbody td { padding: 5px 8px; }
    tbody td.right { text-align: right; }
    tbody td.center { text-align: center; }

    .cat-header { background: #ebf4ff !important; }
    .cat-header td { font-weight: bold; color: #2b6cb0; font-size: 9px; padding: 5px 8px; }
    .cat-header td.right { text-align: right; }

    .cat-bar { display: inline-block; height: 6px; background: #4299e1; border-radius: 3px; vertical-align: middle; margin-right: 4px; }

    .footer { margin: 10px 20px 0; padding-top: 8px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 8px; color: #718096; }

    .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 7.5px; font-weight: bold; }
    .badge-fabric { background: #bee3f8; color: #2b6cb0; }
    .badge-accessory { background: #c6f6d5; color: #276749; }
    .badge-packaging { background: #fefcbf; color: #7b341e; }
    .badge-embroidery { background: #e9d8fd; color: #553c9a; }
    .badge-marketing { background: #fed7e2; color: #97266d; }
    .badge-logistics { background: #feebc8; color: #c05621; }
    .badge-other { background: #e2e8f0; color: #4a5568; }

    .tfoot-row td { font-weight: bold; background: #edf2f7; padding: 6px 8px; border-top: 2px solid #cbd5e0; }
    .tfoot-row td.right { text-align: right; }
</style>
</head>
<body>

<div class="header">
    <div class="meta">
        <div>
            <h1>Material Purchase Report</h1>
            <p>{{ $biz->name ?? 'Business' }} &mdash;
               @if($month) {{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}
               @else {{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
               @endif
            </p>
        </div>
        <div class="right">
            Generated: {{ now()->format('d M Y, h:i A') }}<br>
            @if($month) Period: Full Month @else Period: Custom Range @endif
        </div>
    </div>
</div>

{{-- Summary --}}
<div class="summary-row">
    <div class="summary-card">
        <div class="label">Total Purchases</div>
        <div class="value">{{ $purchases->count() }}</div>
        <div class="sub">items</div>
    </div>
    <div class="summary-card">
        <div class="label">Total Amount</div>
        <div class="value">৳{{ number_format($totalAmount) }}</div>
        <div class="sub">all categories</div>
    </div>
    <div class="summary-card">
        <div class="label">Categories</div>
        <div class="value">{{ $byCategory->count() }}</div>
        <div class="sub">used this period</div>
    </div>
    <div class="summary-card">
        <div class="label">Top Category</div>
        @php $top = $byCategory->sortByDesc('total')->first(); @endphp
        <div class="value" style="font-size:11px;">{{ $top ? ($categories[$top->category] ?? ucfirst($top->category)) : '—' }}</div>
        <div class="sub">৳{{ $top ? number_format($top->total) : 0 }}</div>
    </div>
</div>

{{-- Category Breakdown --}}
<div class="section">
    <div class="section-title">Category Summary</div>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th class="right">Items</th>
                <th class="right">Total Amount</th>
                <th class="right">% of Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byCategory->sortByDesc('total') as $cat)
            @php
                $pct = $totalAmount > 0 ? round($cat->total / $totalAmount * 100, 1) : 0;
                $label = $categories[$cat->category] ?? ucfirst($cat->category);
                $barW = max(1, round($pct));
            @endphp
            <tr>
                <td>
                    <span class="badge badge-{{ $cat->category }}">{{ strtoupper(substr($cat->category,0,3)) }}</span>
                    {{ $label }}
                </td>
                <td class="right">{{ $cat->count }}</td>
                <td class="right">৳{{ number_format($cat->total) }}</td>
                <td class="right">{{ $pct }}%</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="tfoot-row">
                <td>Total</td>
                <td class="right">{{ $purchases->count() }}</td>
                <td class="right">৳{{ number_format($totalAmount) }}</td>
                <td class="right">100%</td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- Detail Table --}}
<div class="section">
    <div class="section-title">Purchase Details</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Item</th>
                <th>Shop</th>
                <th>Color</th>
                <th class="right">Qty</th>
                <th class="right">Rate</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $currentCat = null; @endphp
            @foreach($purchases->sortBy(['category','purchase_date']) as $p)
            @if($p->category !== $currentCat)
            @php $currentCat = $p->category; @endphp
            <tr class="cat-header">
                <td colspan="7">{{ $categories[$p->category] ?? ucfirst($p->category) }}</td>
                <td class="right">৳{{ number_format($purchases->where('category',$p->category)->sum('amount')) }}</td>
            </tr>
            @endif
            <tr>
                <td>{{ \Carbon\Carbon::parse($p->purchase_date)->format('d M') }}</td>
                <td><span class="badge badge-{{ $p->category }}">{{ strtoupper(substr($p->category,0,3)) }}</span></td>
                <td>{{ $p->item_name }}</td>
                <td>{{ $p->shop_name ?? '—' }}</td>
                <td>{{ $p->color ?? '—' }}</td>
                <td class="right">{{ $p->quantity ? number_format($p->quantity,2) : '—' }}</td>
                <td class="right">{{ $p->rate ? '৳'.number_format($p->rate,2) : '—' }}</td>
                <td class="right">৳{{ number_format($p->amount) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="tfoot-row">
                <td colspan="7">Total</td>
                <td class="right">৳{{ number_format($totalAmount) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="footer">
    <span>{{ $biz->name ?? '' }} &mdash; Material Purchase Report</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
