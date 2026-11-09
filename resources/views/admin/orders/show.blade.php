@extends('layouts.admin')

@section('title', 'Order '.$order->order_number)
@section('heading', 'Order '.$order->order_number)

@section('content')
<div class="flex flex-wrap items-center gap-3">
    <span class="badge-gold">{{ $order->status_label }}</span>
    <span class="{{ $order->payment_status === 'paid' ? 'badge-ok' : 'badge-warn' }}">{{ $order->payment_method_label }} · {{ $order->payment_status_label }}</span>
    <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="btn-ghost btn-sm ml-auto">Printable invoice</a>
</div>

<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <section class="card overflow-x-auto" aria-labelledby="items-h">
            <h2 id="items-h" class="border-b border-line px-5 py-4 font-display text-lg">Items</h2>
            <table class="table-x">
                <thead><tr><th>Product</th><th>SKU</th><th>Price</th><th>Qty</th><th class="text-right">Line</th></tr></thead>
                <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }} <span class="text-muted">({{ $item->variant_name }})</span></td>
                        <td class="font-mono text-xs">{{ $item->sku }}</td>
                        <td>{{ money($item->unit_price) }} @if($item->list_price > $item->unit_price)<s class="text-xs text-muted">{{ money($item->list_price) }}</s>@endif</td>
                        <td>{{ $item->quantity }}</td>
                        <td class="text-right">{{ money($item->line_total) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <dl class="space-y-2 border-t border-line p-5 text-sm">
                <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd>{{ money($order->subtotal) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Discount {{ $order->coupon_code ? "({$order->coupon_code})" : '' }}</dt><dd>{{ money($order->discount_total) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Shipping</dt><dd>{{ money($order->shipping_total) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Tax</dt><dd>{{ money($order->tax_total) }}</dd></div>
                <div class="flex justify-between border-t border-line pt-2 font-medium"><dt>Grand total</dt><dd class="text-gold">{{ money($order->grand_total) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Refunded</dt><dd>{{ money($order->refunded_amount) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Net revenue</dt><dd>{{ money($order->net_revenue) }}</dd></div>
            </dl>
        </section>

        <section class="card p-5" aria-labelledby="hist-h">
            <h2 id="hist-h" class="font-display text-lg">Status history</h2>
            <ol class="mt-4 space-y-3 border-l border-line pl-5 text-sm">
                @foreach($order->statusHistories->reverse() as $h)
                    <li>
                        <p><span class="text-muted">{{ $h->from_status ? config("arsh.order_statuses.{$h->from_status}", $h->from_status).' → ' : '' }}</span>{{ config("arsh.order_statuses.{$h->to_status}", $h->to_status) }}</p>
                        <p class="text-xs text-muted">{{ $h->created_at->format('d M Y H:i') }} · {{ $h->changedBy?->name ?? 'System' }}@if($h->note) · {{ $h->note }}@endif</p>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>

    <div class="space-y-6">
        <section class="card space-y-3 p-5 text-sm" aria-labelledby="cust-h">
            <h2 id="cust-h" class="font-display text-lg">Customer</h2>
            <p class="font-medium">{{ $order->name }}</p>
            <p><a class="text-gold hover:underline" href="mailto:{{ $order->email }}">{{ $order->email }}</a></p>
            <p>{{ $order->phone }}</p>
            <p class="text-muted">{{ $order->shippingAddressOneLine() }}</p>
            @if($order->notes)<p class="rounded-sm border border-line p-3 text-muted">Note from customer: {{ $order->notes }}</p>@endif
            @if($order->user_id)<a class="text-xs text-gold hover:underline" href="{{ route('admin.customers.show', $order->user_id) }}">View customer profile</a>@else<p class="text-xs text-muted">Guest checkout</p>@endif
        </section>

        <section class="card space-y-4 p-5" aria-labelledby="status-h">
            <h2 id="status-h" class="font-display text-lg">Update status</h2>
            @if($allowed)
                <form method="post" action="{{ route('admin.orders.status', $order) }}" class="space-y-3">
                    @csrf @method('PATCH')
                    <div><label class="label" for="to-status">New status</label>
                        <select id="to-status" name="status" class="input" required>
                            @foreach($allowed as $s)<option value="{{ $s }}">{{ $statuses[$s] }}</option>@endforeach
                        </select></div>
                    <div><label class="label" for="note">Note (optional)</label><input id="note" name="note" class="input" maxlength="250"></div>
                    @if(in_array('returned', $allowed, true))
                        <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="restock" value="1" class="accent-[#C8A24D]"> Restock items when returned</label>
                    @endif
                    <button class="btn-gold w-full">Update status</button>
                </form>
            @else
                <p class="text-sm text-muted">This order is {{ $order->status_label }} and can no longer change status.</p>
            @endif
        </section>

        <section class="card space-y-3 p-5" aria-labelledby="ship-h">
            <h2 id="ship-h" class="font-display text-lg">Shipment</h2>
            <form method="post" action="{{ route('admin.orders.tracking', $order) }}" class="space-y-3 text-sm">
                @csrf @method('PATCH')
                <div><label class="label" for="courier">Courier</label><input id="courier" name="courier_name" class="input" value="{{ $order->courier_name }}"></div>
                <div><label class="label" for="tn">Tracking number</label><input id="tn" name="tracking_number" class="input" value="{{ $order->tracking_number }}"></div>
                <div><label class="label" for="tu">Tracking URL (https)</label><input id="tu" name="tracking_url" type="url" class="input" value="{{ $order->tracking_url }}"></div>
                <button class="btn-ghost w-full">Save shipment</button>
            </form>
        </section>

        <section class="card space-y-3 p-5" aria-labelledby="pay-h">
            <h2 id="pay-h" class="font-display text-lg">Payment</h2>
            @if($order->payment_method === 'upi')
                @php($upiPayment = $order->payments->firstWhere('provider', 'upi'))
                @php($upiMeta = $upiPayment?->meta ?? [])
                <p class="text-xs text-muted">Direct UPI to {{ $upiMeta['upi']['payee_name'] ?? '' }} ({{ $upiMeta['upi']['upi_id'] ?? '' }})</p>
                @if(!empty($upiMeta['customer_reference']))<p class="text-sm">Customer reference: <strong class="break-all text-gold">{{ $upiMeta['customer_reference'] }}</strong><span class="block text-xs text-muted">Submitted by the customer; verify against your bank receipt.</span></p>@endif
                @if($order->payment_status === 'pending' && $order->status === 'pending')
                    <form method="post" action="{{ route('admin.orders.verify-upi', $order) }}" class="space-y-4">
                        @csrf
                        <div><label class="label" for="upi-reference">Verified transaction reference / UTR</label><input id="upi-reference" name="reference" class="input" required pattern="[A-Za-z0-9]{6,64}" maxlength="64" value="{{ old('reference', $upiMeta['customer_reference'] ?? '') }}"><p class="mt-2 text-xs text-muted">Match the credited amount of {{ money($order->grand_total) }} and reference in your bank or UPI app.</p></div>
                        <label class="flex items-start gap-3 text-xs"><input type="checkbox" name="receipt_verified" value="1" required class="mt-1"><span>I verified that the full order amount was credited to the receiving account.</span></label>
                        <button type="submit" class="btn-gold w-full">Verify UPI payment received</button>
                    </form>
                @endif
                @if(!empty($upiMeta['verified_reference']))<p class="text-xs text-ok">Verified reference: {{ $upiMeta['verified_reference'] }}</p>@endif
            @endif
            @if($order->payment_method === 'cod' && $order->payment_status === 'pending' && $order->status !== 'cancelled')
                <form method="post" action="{{ route('admin.orders.mark-paid', $order) }}" onsubmit="return confirm('Confirm the cash has been collected?')">
                    @csrf<button class="btn-ghost w-full">Mark cash collected</button>
                </form>
            @endif
            @if($order->payment_status === 'paid')
                <form method="post" action="{{ route('admin.orders.refund', $order) }}" class="space-y-3 text-sm" onsubmit="return confirm('Record this refund?')">
                    @csrf
                    <div><label class="label" for="ramt">Amount (blank = full remaining)</label><input id="ramt" name="amount" type="number" step="0.01" min="0.01" class="input"></div>
                    <div><label class="label" for="rnote">Reason</label><input id="rnote" name="note" class="input" required maxlength="250"></div>
                    <button class="btn-danger w-full">Record refund</button>
                    @if($order->payment_method === 'razorpay')<p class="text-xs text-muted">Online refunds are sent to Razorpay automatically in live mode.</p>@endif
                    @if($order->payment_method === 'upi')<p class="text-xs text-muted">Send the refund separately from your bank or UPI app before recording it here. This button records the refund and does not transfer money.</p>@endif
                </form>
            @endif
            @if($order->payments->isNotEmpty())
                <ul class="space-y-1 text-xs text-muted">
                    @foreach($order->payments as $p)
                        <li>{{ strtoupper($p->provider) }} · {{ $p->status }} · {{ $p->provider_payment_id ?: $p->provider_order_id ?: '—' }}@if($p->error_description) · {{ $p->error_description }}@endif</li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div>
@endsection
