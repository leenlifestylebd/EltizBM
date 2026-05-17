<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Low Stock Alert</title>
<style>
  body { font-family: Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 0; }
  .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
  .header { background: #d97706; color: #fff; padding: 28px 32px; }
  .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
  .header p  { margin: 4px 0 0; font-size: 13px; opacity: 0.85; }
  .body { padding: 28px 32px; }
  .section-title { font-size: 13px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; margin: 20px 0 8px; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th { background: #f9fafb; text-align: left; padding: 8px 10px; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
  td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; color: #374151; }
  .badge-out  { background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
  .badge-low  { background: #fef3c7; color: #d97706; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
  .footer { background: #f9fafb; padding: 18px 32px; font-size: 12px; color: #9ca3af; border-top: 1px solid #f3f4f6; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>⚠️ Low Stock Alert</h1>
    <p>{{ $businessName }} — {{ now()->format('d M Y') }}</p>
  </div>
  <div class="body">
    <p style="font-size:14px;color:#374151;">
      The following products need your attention. Please restock to avoid order fulfillment issues.
    </p>

    @if($outOfStockProducts->count() > 0)
    <div class="section-title" style="color:#dc2626;">Out of Stock ({{ $outOfStockProducts->count() }})</div>
    <table>
      <thead>
        <tr>
          <th>Product</th><th>SKU</th><th style="text-align:right">Stock</th><th>Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($outOfStockProducts as $p)
        <tr>
          <td>{{ $p->name }}</td>
          <td style="font-family:monospace;color:#6b7280;">{{ $p->sku ?? '—' }}</td>
          <td style="text-align:right;font-weight:700;color:#dc2626;">{{ $p->stock }}</td>
          <td><span class="badge-out">Out of Stock</span></td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif

    @if($lowStockProducts->count() > 0)
    <div class="section-title" style="color:#d97706;">Low Stock ({{ $lowStockProducts->count() }})</div>
    <table>
      <thead>
        <tr>
          <th>Product</th><th>SKU</th><th style="text-align:right">Stock</th><th style="text-align:right">Alert At</th><th>Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($lowStockProducts as $p)
        <tr>
          <td>{{ $p->name }}</td>
          <td style="font-family:monospace;color:#6b7280;">{{ $p->sku ?? '—' }}</td>
          <td style="text-align:right;font-weight:700;color:#d97706;">{{ $p->stock }}</td>
          <td style="text-align:right;color:#6b7280;">{{ $p->low_stock_alert }}</td>
          <td><span class="badge-low">Low Stock</span></td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif

    <p style="font-size:13px;color:#6b7280;margin-top:24px;">
      This is an automated daily alert. Log in to your dashboard to manage stock.
    </p>
  </div>
  <div class="footer">
    Sent by {{ $businessName }} ERP · Automated Alert System
  </div>
</div>
</body>
</html>
