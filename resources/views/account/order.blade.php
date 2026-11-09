@extends('account.layout')

@section('account')
@if($order->payment_method === 'upi' && $order->payment_status === 'pending' && $order->status === 'pending')
    <div class="alert-info mb-6">UPI payment is awaiting verification. <a href="{{ route('checkout.upi', $order) }}" class="underline">View QR or submit your payment reference</a>.</div>
@endif
<div class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="section-title">Order {{ $order->order_number }}</h1>
        <p class="mt-2 text-xs text-muted">Placed {{ $order->placed_at?->format('d M Y, h:i A') }}</p>
    </div>
    <span class="{{ in_array($order->status, ['cancelled', 'returned']) ? 'badge-bad' : 'badge-gold' }}">{{ $order->status_label }}</span>
</div>

<div class="mt-8 card divide-y divide-line">
    @foreach($order->items as $item)
        <div class="flex justify-between gap-4 p-4 text-sm">
            <div>
                <p class="font-medium">{{ $item->product_name }}</p>
                <p class="text-xs text-muted">{{ $item->variant_name }} &middot; Qty {{ $item->quantity }} &middot; {{ money($item->unit_price) }} each</p>
            </div>
            <p>{{ money($item->line_total) }}</p>
        </div>
    @endforeach
    <dl class="space-y-2 p-4 text-sm">
        <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd>{{ money($order->subtotal) }}</dd></div>
        @if((float) $order->discount_total > 0)<div class="flex justify-between text-ok"><dt>Discount {{ $order->coupon_code ? "($order->coupon_code)" : '' }}</dt><dd>&minus;{{ money($order->discount_total) }}</dd></div>@endif
        <div class="flex justify-between"><dt class="text-muted">Shipping</dt><dd>{{ (float) $order->shipping_total > 0 ? money($order->shipping_total) : 'Free' }}</dd></div>
        <div class="flex justify-between border-t border-line pt-2 font-medium"><dt>Total</dt><dd class="text-gold">{{ money($order->grand_total) }}</dd></div>
        <div class="flex justify-between text-muted"><dt>Payment</dt><dd>{{ $order->payment_method_label }} &middot; {{ $order->payment_status_label }}</dd></div>
    </dl>
</div>

<div class="mt-8 grid gap-6 md:grid-cols-2">
    <div class="card p-5 text-sm">
        <p class="label">Delivery address</p>
        <p>{{ $order->name }}</p>
        <p class="text-muted">{{ $order->shippingAddressOneLine() }}</p>
        <p class="mt-2 text-muted">{{ $order->phone }}</p>
        @if($order->tracking_number)
            <p class="mt-4 text-muted">Courier: {{ $order->courier_name ?: '—' }} &middot; Tracking: {{ $order->tracking_number }}</p>
        @endif
    </div>
    <div class="card p-5 text-sm">
        <p class="label">History</p>
        <ol class="space-y-2">
            @foreach($order->statusHistories->reverse() as $h)
                <li><span class="text-white">{{ config("arsh.order_statuses.{$h->to_status}", $h->to_status) }}</span> <span class="text-xs text-muted">{{ $h->created_at->format('d M Y, h:i A') }}</span></li>
            @endforeach
        </ol>
    </div>
</div>

<div class="mt-8 flex flex-wrap gap-4">
    @can('cancel', $order)
        <form method="post" action="{{ route('account.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?')">
            @csrf
            <button class="btn-danger btn-sm">Cancel order</button>
        </form>
    @endcan
    <a href="{{ route('track-order') }}" class="btn-ghost btn-sm">Track shipment</a>
</div>
@endsection
