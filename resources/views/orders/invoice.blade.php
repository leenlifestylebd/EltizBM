<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { font-family: 'Arial', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .invoice-wrap { box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-4">

    {{-- Print Button --}}
    <div class="no-print flex justify-center gap-3 mb-6">
        <button onclick="window.print()"
                class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print Invoice
        </button>
        <a href="{{ route('orders.show', $order) }}"
           class="px-5 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
            Back to Order
        </a>
    </div>

    {{-- Invoice --}}
    <div class="invoice-wrap max-w-2xl mx-auto bg-white rounded-2xl shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="bg-blue-600 px-6 py-6 text-white">
            <div class="flex items-start justify-between">
                <div>
                    @if($business->logo)
                        <img src="{{ Storage::url($business->logo) }}" alt="{{ $business->name }}" class="h-10 mb-2 object-contain brightness-0 invert">
                    @endif
                    <h1 class="text-xl font-bold">{{ $business->name }}</h1>
                    @if($business->phone)
                        <p class="text-blue-100 text-sm mt-0.5">{{ $business->phone }}</p>
                    @endif
                    @if($business->address)
                        <p class="text-blue-100 text-xs mt-0.5">{{ $business->address }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-blue-100 text-xs uppercase tracking-wider font-semibold">Invoice</p>
                    <p class="text-2xl font-bold mt-1">#{{ $order->order_number }}</p>
                    <p class="text-blue-100 text-xs mt-1">{{ $order->created_at->format('d M Y') }}</p>
                </div>
            </div>
        </div>

        <div class="px-6 py-5">

            {{-- Customer + Status --}}
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Bill To</p>
                    <p class="font-semibold text-gray-900">{{ $order->customer_name }}</p>
                    @if($order->customer_phone)
                        <p class="text-sm text-gray-600 mt-0.5">{{ $order->customer_phone }}</p>
                    @endif
                    @if($order->shipping_address)
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $order->shipping_address }}</p>
                    @endif
                    @if($order->shipping_area || $order->shipping_district)
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ implode(', ', array_filter([$order->shipping_area, $order->shipping_district])) }}
                        </p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Details</p>
                    <div class="space-y-1">
                        <div class="flex justify-end gap-2">
                            <span class="text-xs text-gray-500">Status:</span>
                            <span class="text-xs font-semibold text-gray-900">{{ ucfirst($order->status) }}</span>
                        </div>
                        <div class="flex justify-end gap-2">
                            <span class="text-xs text-gray-500">Payment:</span>
                            <span class="text-xs font-semibold text-gray-900">{{ ucfirst($order->payment_method) }}</span>
                        </div>
                        <div class="flex justify-end gap-2">
                            <span class="text-xs text-gray-500">Date:</span>
                            <span class="text-xs font-semibold text-gray-900">{{ $order->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items Table --}}
            <table class="w-full mb-5">
                <thead>
                    <tr class="bg-gray-50 border border-gray-100">
                        <th class="text-left px-3 py-2.5 text-xs font-semibold text-gray-600 rounded-l-lg">#</th>
                        <th class="text-left px-3 py-2.5 text-xs font-semibold text-gray-600">Item</th>
                        <th class="text-center px-3 py-2.5 text-xs font-semibold text-gray-600">Qty</th>
                        <th class="text-right px-3 py-2.5 text-xs font-semibold text-gray-600">Price</th>
                        <th class="text-right px-3 py-2.5 text-xs font-semibold text-gray-600 rounded-r-lg">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $i => $item)
                    <tr class="border-b border-gray-50">
                        <td class="px-3 py-3 text-xs text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-3 py-3">
                            <p class="text-sm font-medium text-gray-900">{{ $item->product_name }}</p>
                            @if($item->sku)
                                <p class="text-xs text-gray-400 font-mono">{{ $item->sku }}</p>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-center text-sm text-gray-700">{{ $item->quantity }}</td>
                        <td class="px-3 py-3 text-right text-sm text-gray-700">৳{{ number_format($item->price) }}</td>
                        <td class="px-3 py-3 text-right text-sm font-semibold text-gray-900">৳{{ number_format($item->total) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Totals --}}
            <div class="flex justify-end mb-6">
                <div class="w-56">
                    <div class="flex justify-between py-1.5 text-sm text-gray-600">
                        <span>Subtotal</span>
                        <span>৳{{ number_format($order->subtotal) }}</span>
                    </div>
                    @if($order->discount > 0)
                    <div class="flex justify-between py-1.5 text-sm text-red-600">
                        <span>Discount</span>
                        <span>-৳{{ number_format($order->discount) }}</span>
                    </div>
                    @endif
                    @if($order->shipping_charge > 0)
                    <div class="flex justify-between py-1.5 text-sm text-gray-600">
                        <span>Shipping</span>
                        <span>৳{{ number_format($order->shipping_charge) }}</span>
                    </div>
                    @endif
                    @if($order->vat > 0)
                    <div class="flex justify-between py-1.5 text-sm text-gray-600">
                        <span>VAT</span>
                        <span>৳{{ number_format($order->vat) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-2.5 text-base font-bold text-gray-900 border-t-2 border-gray-200 mt-1">
                        <span>Total</span>
                        <span>৳{{ number_format($order->total) }}</span>
                    </div>
                    @if($order->paid_amount > 0)
                    <div class="flex justify-between py-1.5 text-sm text-green-600">
                        <span>Paid</span>
                        <span>৳{{ number_format($order->paid_amount) }}</span>
                    </div>
                    @endif
                    @if($order->due_amount > 0)
                    <div class="flex justify-between py-1.5 text-sm font-bold text-red-600">
                        <span>Due</span>
                        <span>৳{{ number_format($order->due_amount) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Notes --}}
            @if($order->notes)
            <div class="bg-gray-50 rounded-lg p-3 mb-4">
                <p class="text-xs font-semibold text-gray-500 mb-1">Notes:</p>
                <p class="text-xs text-gray-600">{{ $order->notes }}</p>
            </div>
            @endif

            {{-- Footer --}}
            <div class="border-t border-gray-100 pt-4 text-center">
                <p class="text-xs text-gray-400">Thank you for your business! — {{ $business->name }}</p>
                @if($business->phone)
                    <p class="text-xs text-gray-400 mt-0.5">{{ $business->phone }}
                        @if($business->email) · {{ $business->email }} @endif
                    </p>
                @endif
            </div>

        </div>
    </div>

</body>
</html>
