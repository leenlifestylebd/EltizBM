<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Received</title>
<style>
  body { font-family: Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 0; }
  .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
  .header { background: #16a34a; color: #fff; padding: 28px 32px; }
  .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
  .header p  { margin: 4px 0 0; font-size: 13px; opacity: 0.85; }
  .body { padding: 28px 32px; }
  .amount-box { background: #f0fdf4; border: 2px solid #bbf7d0; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
  .amount-box .amount { font-size: 36px; font-weight: 700; color: #15803d; }
  .amount-box .label  { font-size: 13px; color: #6b7280; margin-top: 4px; }
  .meta-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
  .meta-row .key { color: #6b7280; }
  .meta-row .val { color: #111827; font-weight: 500; }
  .footer { background: #f9fafb; padding: 18px 32px; font-size: 12px; color: #9ca3af; border-top: 1px solid #f3f4f6; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Payment Received ✓</h1>
    <p>{{ $order->business?->name ?? config('app.name') }}</p>
  </div>
  <div class="body">
    <p style="font-size:15px;color:#374151;">Hi <strong>{{ $order->customer_name }}</strong>,</p>
    <p style="font-size:14px;color:#374151;">We've received your payment. Thank you!</p>

    <div class="amount-box">
      <div class="amount">৳{{ number_format($amount, 2) }}</div>
      <div class="label">Payment Received</div>
    </div>

    <div>
      <div class="meta-row">
        <span class="key">Order Number</span>
        <span class="val">#{{ $order->order_number }}</span>
      </div>
      <div class="meta-row">
        <span class="key">Payment Method</span>
        <span class="val">{{ ucfirst($order->payment_method) }}</span>
      </div>
      <div class="meta-row">
        <span class="key">Order Total</span>
        <span class="val">৳{{ number_format($order->total, 2) }}</span>
      </div>
      @if($order->due_amount > 0)
      <div class="meta-row">
        <span class="key" style="color:#ef4444;">Remaining Due</span>
        <span class="val" style="color:#ef4444;">৳{{ number_format($order->due_amount, 2) }}</span>
      </div>
      @else
      <div class="meta-row">
        <span class="key" style="color:#16a34a;">Payment Status</span>
        <span class="val" style="color:#16a34a;">Fully Paid ✓</span>
      </div>
      @endif
    </div>

    <p style="font-size:13px;color:#6b7280;margin-top:20px;">
      Keep this email as your payment receipt. Contact us if you have any questions.
    </p>
  </div>
  <div class="footer">
    This is an automated message from {{ $order->business?->name ?? config('app.name') }}.
  </div>
</div>
</body>
</html>
