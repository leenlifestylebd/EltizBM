<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_number }} — {{ $business->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { font-family: 'Arial', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; margin: 0; padding: 0; }
            .invoice-wrap { box-shadow: none !important; border-radius: 0 !important; max-width: 100% !important; }
            @page { margin: 0.5cm; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-6 px-4">

{{-- Action Buttons --}}
<div class="no-print max-w-3xl mx-auto mb-4 flex flex-wrap gap-2 justify-center">
    <button onclick="window.print()"
            class="flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium rounded-xl transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
        </svg>
        Print
    </button>
    <a href="{{ route('orders.invoice.pdf', $order) }}"
       class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Download PDF
    </a>
    <a href="{{ route('orders.receipt', $order) }}" target="_blank"
       class="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-xl transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Thermal Receipt
    </a>
    <a href="{{ route('orders.show', $order) }}"
       class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Back
    </a>
</div>

{{-- Invoice Paper --}}
<div class="invoice-wrap max-w-3xl mx-auto bg-white shadow-lg rounded-2xl overflow-hidden">

    {{-- Header --}}
    @php
        $themeColor = $business->themeColor('600');
    @endphp
    <div style="background: {{ $themeColor }};" class="px-8 py-7">
        <div class="flex items-start justify-between">
            <div>
                @if($business->logo)
                    <img src="{{ Storage::url($business->logo) }}" alt="{{ $business->name }}"
                         class="h-12 mb-3 object-contain brightness-0 invert">
                @endif
                <h1 class="text-white text-xl font-bold tracking-tight">{{ $business->name }}</h1>
                @if($business->phone)
                    <p class="text-white/80 text-sm mt-0.5">📞 {{ $business->phone }}</p>
                @endif
                @if($business->email)
                    <p class="text-white/80 text-sm mt-0.5">✉ {{ $business->email }}</p>
                @endif
                @if($business->address)
                    <p class="text-white/70 text-xs mt-1 max-w-xs">{{ $business->address }}</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-white/60 text-xs font-semibold uppercase tracking-widest mb-1">Invoice</p>
                <p class="text-white text-3xl font-black">#{{ $order->order_number }}</p>
                <div class="mt-3 text-right space-y-1">
                    <p class="text-white/70 text-xs">Date: <span class="text-white font-medium">{{ $order->created_at->format('d M Y') }}</span></p>
                    @php
                        $payBadge = match($order->payment_status) {
                            'paid'    => ['bg' => 'bg-green-500',  'text' => 'PAID'],
                            'partial' => ['bg' => 'bg-yellow-500', 'text' => 'PARTIAL'],
                            default   => ['bg' => 'bg-red-500',    'text' => 'UNPAID'],
                        };
                    @endphp
                    <span class="inline-block {{ $payBadge['bg'] }} text-white text-xs font-bold px-3 py-1 rounded-full">
                        {{ $payBadge['text'] }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="px-8 py-6">

        {{-- Bill To + Order Details --}}
        <div class="grid grid-cols-2 gap-6 mb-7 pb-6 border-b border-gray-100">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Bill To</p>
                <p class="font-bold text-gray-900 text-base">{{ $order->customer_name }}</p>
                @if($order->customer_phone)
                    <p class="text-sm text-gray-600 mt-1">{{ $order->customer_phone }}</p>
                @endif
                @if($order->shipping_address)
                    <p class="text-sm text-gray-500 mt-1 leading-relaxed">{{ $order->shipping_address }}</p>
                @endif
                @if($order->shipping_area || $order->shipping_district)
                    <p class="text-sm text-gray-500">
                        {{ implode(', ', array_filter([$order->shipping_area, $order->shipping_district])) }}
                    </p>
                @endif
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Order Details</p>
                <table class="text-sm w-full">
                    <tr>
                        <td class="text-gray-500 py-0.5 pr-3">Order #</td>
                        <td class="font-semibold text-gray-800">{{ $order->order_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-gray-500 py-0.5 pr-3">Date</td>
                        <td class="font-semibold text-gray-800">{{ $order->created_at->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-gray-500 py-0.5 pr-3">Status</td>
                        <td class="font-semibold text-gray-800 capitalize">{{ $order->status }}</td>
                    </tr>
                    <tr>
                        <td class="text-gray-500 py-0.5 pr-3">Payment</td>
                        <td class="font-semibold text-gray-800 capitalize">{{ $order->payment_method }}</td>
                    </tr>
                    @if($order->shipments->first()?->tracking_number)
                    <tr>
                        <td class="text-gray-500 py-0.5 pr-3">Tracking</td>
                        <td class="font-semibold text-gray-800 font-mono text-xs">{{ $order->shipments->first()->tracking_number }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- Items Table --}}
        <table class="w-full mb-6">
            <thead>
                <tr style="background: {{ $themeColor }}10;">
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider rounded-l-xl w-8">#</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Item</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider w-16">Qty</th>
                    <th class="text-right px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider w-24">Unit Price</th>
                    @if($order->items->sum('discount') > 0)
                    <th class="text-right px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider w-20">Disc.</th>
                    @endif
                    <th class="text-right px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider w-28 rounded-r-xl">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($order->items as $i => $item)
                <tr class="hover:bg-gray-50/50">
                    <td class="px-4 py-3.5 text-xs text-gray-400 font-mono">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-4 py-3.5">
                        <p class="font-semibold text-gray-900 text-sm">{{ $item->product_name }}</p>
                        @if($item->sku)
                            <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $item->sku }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <span class="text-sm font-semibold text-gray-800">{{ $item->quantity }}</span>
                    </td>
                    <td class="px-4 py-3.5 text-right text-sm text-gray-700">
                        {{ $business->currency_symbol ?? '৳' }}{{ number_format($item->price, 0) }}
                    </td>
                    @if($order->items->sum('discount') > 0)
                    <td class="px-4 py-3.5 text-right text-sm text-red-500">
                        {{ $item->discount > 0 ? '-'.$business->currency_symbol ?? '৳'.number_format($item->discount,0) : '—' }}
                    </td>
                    @endif
                    <td class="px-4 py-3.5 text-right">
                        <span class="text-sm font-bold text-gray-900">
                            {{ $business->currency_symbol ?? '৳' }}{{ number_format($item->total, 0) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Summary --}}
        <div class="flex justify-end mb-6">
            <div class="w-64">
                <div class="space-y-2">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Subtotal</span>
                        <span>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->subtotal, 0) }}</span>
                    </div>
                    @if($order->discount > 0)
                    <div class="flex justify-between text-sm text-red-600">
                        <span>Discount</span>
                        <span>−{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->discount, 0) }}</span>
                    </div>
                    @endif
                    @if($order->shipping_charge > 0)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Shipping</span>
                        <span>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->shipping_charge, 0) }}</span>
                    </div>
                    @endif
                    @if($order->vat > 0)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>VAT</span>
                        <span>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->vat, 0) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-base font-black text-gray-900 border-t-2 border-gray-200 pt-2.5 mt-1">
                        <span>Grand Total</span>
                        <span style="color: {{ $themeColor }}">{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->total, 0) }}</span>
                    </div>
                </div>

                {{-- Payment Summary --}}
                @if($order->paid_amount > 0 || $order->due_amount > 0)
                <div class="mt-3 pt-3 border-t border-dashed border-gray-200 space-y-1.5">
                    @if($order->paid_amount > 0)
                    <div class="flex justify-between text-sm font-medium text-green-600">
                        <span>✓ Paid</span>
                        <span>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->paid_amount, 0) }}</span>
                    </div>
                    @endif
                    @if($order->due_amount > 0)
                    <div class="flex justify-between text-sm font-bold text-red-600">
                        <span>⚠ Due</span>
                        <span>{{ $business->currency_symbol ?? '৳' }}{{ number_format($order->due_amount, 0) }}</span>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- Notes --}}
        @if($order->notes)
        <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 mb-6">
            <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">Notes</p>
            <p class="text-sm text-amber-800">{{ $order->notes }}</p>
        </div>
        @endif

        {{-- Footer --}}
        <div class="border-t border-gray-100 pt-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-700">{{ $business->name }}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ implode(' · ', array_filter([$business->phone, $business->email])) }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">Thank you for your business!</p>
                <p class="text-xs text-gray-300 mt-0.5">Generated {{ now()->format('d M Y, H:i') }}</p>
            </div>
        </div>

    </div>
</div>

</body>
</html>
