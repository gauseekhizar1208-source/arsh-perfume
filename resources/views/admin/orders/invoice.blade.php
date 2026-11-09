<!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <title>Invoice {{ $order->invoice_number }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color:#111; margin:32px; font-size:13px; }
        h1 { font-size:20px; margin:0 0 4px; letter-spacing:.08em; }
        table { width:100%; border-collapse:collapse; margin-top:16px; }
        th, td { border-bottom:1px solid #ddd; padding:8px 6px; text-align:left; }
        th.r, td.r { text-align:right; }
        .muted { color:#666; }
        .totals td { border:0; }
        @media print { .noprint { display:none; } body { margin:12mm; } }
    </style>
</head>
<body>
<div class="noprint" style="text-align:right"><button onclick="window.print()">Print</button></div>
<header style="display:flex;justify-content:space-between;gap:24px">
    <div>
        <h1>{{ setting('business_name', 'ARSH PERFUME') }}</h1>
        <div class="muted">{!! nl2br(e(setting('business_address'))) !!}</div>
        @if(setting('gstin'))<div class="muted">GSTIN: {{ setting('gstin') }}</div>@endif
        @if(setting('contact_email'))<div class="muted">{{ setting('contact_email') }} @if(setting('contact_phone')) · {{ setting('contact_phone') }}@endif</div>@endif
    </div>
    <div style="text-align:right">
        <strong>TAX INVOICE</strong><br>
        Invoice: {{ $order->invoice_number }}<br>
        Order: {{ $order->order_number }}<br>
        Date: {{ $order->placed_at?->format('d M Y') }}<br>
        Payment: {{ $order->payment_method_label }} ({{ $order->payment_status_label }})
    </div>
</header>

<section style="margin-top:24px">
    <strong>Bill to / ship to</strong><br>
    {{ $order->name }}<br>
    {{ $order->shippingAddressOneLine() }}<br>
    {{ $order->phone }} · {{ $order->email }}
</section>

<table>
    <thead><tr><th>#</th><th>Product</th><th>SKU</th><th class="r">Unit price</th><th class="r">Qty</th><th class="r">Amount</th></tr></thead>
    <tbody>
    @foreach($order->items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $item->product_name }}@if($item->variant_name)<br><span class="muted">{{ $item->variant_name }}</span>@endif</td>
            <td>{{ $item->sku }}</td>
            <td class="r">{{ money($item->unit_price) }}</td>
            <td class="r">{{ $item->quantity }}</td>
            <td class="r">{{ money($item->line_total) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals" style="width:40%;margin-left:auto">
    <tr><td>Subtotal</td><td class="r">{{ money($order->subtotal) }}</td></tr>
    @if((float) $order->discount_total > 0)<tr><td>Discount</td><td class="r">&minus;{{ money($order->discount_total) }}</td></tr>@endif
    <tr><td>Shipping</td><td class="r">{{ money($order->shipping_total) }}</td></tr>
    @if((float) $order->tax_total > 0)<tr><td>GST (included)</td><td class="r">{{ money($order->tax_total) }}</td></tr>@endif
    <tr><td><strong>Total</strong></td><td class="r"><strong>{{ money($order->grand_total) }}</strong></td></tr>
</table>

<footer style="margin-top:40px" class="muted">
    <div>{{ setting('invoice_footer') }}</div>
    @if(setting('invoice_terms'))<div style="margin-top:8px">{!! nl2br(e(setting('invoice_terms'))) !!}</div>@endif
</footer>
</body>
</html>
