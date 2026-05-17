<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Confirmed</title>
<style>
  body { font-family: Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 0; }
  .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
  .header { background: #2563eb; color: #fff; padding: 28px 32px; }
  .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
  .header p  { margin: 4px 0 0; font-size: 13px; opacity: 0.85; }
  .body { padding: 28px 32px; }
  .greeting { font-size: 15px; color: #374151; margin-bottom: 16px; }
  .order-box { background: #f0f4ff; border: 1px solid #dbeafe; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; }
  .order-box .order-num { font-size: 20px; font-weight: 700; color: #1d4ed8; }
  .order-box .meta { font-size: 13px; color: #6b7280; margin-top: 4px; }
  table.items { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 13px; }
  table.items th { background: #f9fafb; text-align: left; padding: 8px 10px; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
  table.items td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; color: #374151; }
  .totals { text-align: right; font-size: 13px; color: #374151; }
  .totals .grand { font-size: 17px; font-weight: 700; color: #1d4ed8; }
  .footer { background: #f9fafb; padding: 18px 32px; font-size: 12px; color: #9ca3af; border-top: 1px solid #f3f4f6; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Order Confirmed ✓</h1>
    <p>{{ $order->business?->name ?? config('app.name') }}</p>
  </div>
  <div class="body">
    <p class="greeting">Hi <strong>{{ $order->customer_name }}</strong>,</p>
    <p style="font-size:14px;color:#374151;margin-bottom:20px;">
      Thank you for your order! We've received it and it's being processed.
    </p>

    <div class="order-box">
      <div class="order-num">#{{ $order->order_number }}</div>
      <div class="meta">
        Placed: {{ $order->created_at->format('d M Y, h:i A') }} &nbsp;·&nbsp;
        Status: <strong>{{ ucfirst($order->status) }}</strong>
      </div>
    </div>

    @if($order->items && $order->items->count() > 0)
    <table class="items">
      <thead>
        <tr>
          <th>Product</th>
          <th style="text-align:center">Qty</th>
          <th style="text-align:right">Price</th>
          <th style="text-align:right">Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach($order->items as $item)
        <tr>
          <td>{{ $item->product_name }}</td>
          <td style="text-align:center">{{ $item->quantity }}</td>
          <td style="text-align:right">৳{{ number_format($item->price, 2) }}</td>
          <td style="text-align:right">৳{{ number_format($item->total, 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif

    <div class="totals">
      @if($order->discount > 0)
      <div>Discount: -৳{{ number_format($order->discount, 2) }}</div>
      @endif
      @if($order->shipping_charge > 0)
      <div>Shipping: ৳{{ number_format($order->shipping_charge, 2) }}</div>
      @endif
      <div class="grand">Total: ৳{{ number_format($order->total, 2) }}</div>
      @if($order->due_amount > 0)
      <div style="color:#ef4444;font-size:13px;margin-top:4px;">Due: ৳{{ number_format($order->due_amount, 2) }}</div>
      @endif
    </div>

    @if($order->shipping_address)
    <div style="margin-top:20px;padding:12px 16px;background:#f9fafb;border-radius:8px;font-size:13px;color:#6b7280;">
      <strong>Delivery Address:</strong><br>{{ $order->shipping_address }}
    </div>
    @endif

    <p style="font-size:13px;color:#6b7280;margin-top:20px;">
      If you have any questions, please contact us. We'll keep you updated on your order status.
    </p>
  </div>
  <div class="footer">
    This is an automated message from {{ $order->business?->name ?? config('app.name') }}. Please do not reply to this email.
  </div>
</div>
</body>
</html>
