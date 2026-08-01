@php
    $order   = $shipment->order;

    $address = trim(implode(', ', array_filter([
        $order->shipping_address  ?? '',
        $order->shipping_area     ?? '',
        $order->shipping_district ?? '',
    ])));

    $printTime = now()->format('d/m/y h:iA');

    $merchantId = $business->print_settings['label_merchant_id'] ?? null;
    $merchantId = $merchantId ?: strtoupper(substr(md5($business->id ?? 1), 0, 8));
@endphp

<div class="label" id="label-{{ $shipment->id ?? 'preview' }}">

    {{-- ① HEADER ──────────────────────────────────── --}}
    <div class="sec-header">
        @if($business->logo ?? null)
        <div class="header-logo-row">
            <img src="{{ asset('storage/'.$business->logo) }}"
                 alt="{{ $business->name }}" class="biz-logo">
        </div>
        @endif
        <div class="header-meta">
            <span class="biz-name">{{ Str::upper($business->name) }}</span>
            <div class="merchant-block">
                <span class="merchant-lbl">Merchant ID</span>
                <span class="merchant-id">{{ $merchantId }}</span>
            </div>
        </div>
    </div>

    {{-- ② BARCODE ─────────────────────────────────── --}}
    <div class="sec-barcode">
        @if($shipment->tracking_id)
            <svg class="barcode-svg" data-tracking="{{ $shipment->tracking_id }}"></svg>
            <div class="barcode-num">{{ $shipment->tracking_id }}</div>
        @else
            <div class="barcode-num" style="padding:10px 0;font-size:9px;">No Tracking ID</div>
        @endif
    </div>

    {{-- ③ CONSIGNMENT ID ──────────────────────────── --}}
    <div class="sec-cid">
        <span class="cid-key">CID</span>
        <span class="cid-val">{{ $shipment->consignment_id ?? $order->order_number ?? '—' }}</span>
    </div>

    {{-- ④ CUSTOMER ────────────────────────────────── --}}
    <div class="sec-customer">
        <div class="row">
            <span class="row-key">Name</span>
            <span class="row-val val-name">{{ $order->customer_name ?? '—' }}</span>
        </div>
        <div class="row">
            <span class="row-key">Phone</span>
            <span class="row-val val-phone">{{ $order->customer_phone ?? '—' }}</span>
        </div>
        @if($address)
        <div class="row">
            <span class="row-key">Address</span>
            <span class="row-val">{{ $address }}</span>
        </div>
        @endif
    </div>

    {{-- ⑤ ITEMS ───────────────────────────────────── --}}
    @if($order->items && $order->items->count())
    <div class="sec-items">
        @foreach($order->items->take(5) as $item)
        @php
            // Short product name at the start, SKU (+ qty) at the end of the same line.
            $sku    = $item->sku ?: (optional($item->variant)->sku ?: optional($item->product)->sku);
            $detail = trim(($sku ? $sku . ' ' : '') . '×' . $item->quantity);
        @endphp
        <div class="item-row">
            <span class="item-name">{{ Str::limit($item->product_name, 14, '') }}</span>
            <span class="item-detail">{{ $detail }}</span>
        </div>
        @endforeach
        @if($order->items->count() > 4)
        <div class="item-more">+{{ $order->items->count() - 4 }} more item(s)</div>
        @endif
    </div>
    @else
    <div class="sec-items"></div>
    @endif

    {{-- ⑥ COD BAND ────────────────────────────────── --}}
    <div class="sec-cod">
        <span class="cod-lbl">Cash on Delivery</span>
        <span class="cod-amt">৳{{ number_format($shipment->cod_amount) }}</span>
    </div>

    {{-- ⑦ FOOTER ─────────────────────────────────── --}}
    <div class="sec-footer">
        <span class="footer-date">{{ $printTime }}</span>
        <span class="footer-brand">✈ SteadFast</span>
    </div>

</div>
