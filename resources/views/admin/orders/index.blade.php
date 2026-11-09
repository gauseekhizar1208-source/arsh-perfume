@extends('layouts.admin')

@section('title', 'Orders')
@section('heading', 'Orders')

@section('content')
<form method="get" class="card grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-6 xl:items-end">
    <div class="xl:col-span-2"><label class="label" for="q">Search</label><input id="q" name="q" class="input py-2" value="{{ $filters['q'] ?? '' }}" placeholder="Order no., name, email, phone"></div>
    <div><label class="label" for="status">Order status</label>
        <select id="status" name="status" class="input py-2"><option value="">All</option>@foreach($statuses as $k => $l)<option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div><label class="label" for="payment_status">Payment</label>
        <select id="payment_status" name="payment_status" class="input py-2"><option value="">All</option>@foreach(config('arsh.payment_statuses') as $k => $l)<option value="{{ $k }}" @selected(($filters['payment_status'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div><label class="label" for="payment_method">Method</label>
        <select id="payment_method" name="payment_method" class="input py-2"><option value="">All</option><option value="cod" @selected(($filters['payment_method'] ?? '') === 'cod')>Cash on Delivery</option><option value="razorpay" @selected(($filters['payment_method'] ?? '') === 'razorpay')>Online</option><option value="upi" @selected(($filters['payment_method'] ?? '') === 'upi')>UPI QR</option></select></div>
    <div class="flex gap-2"><button class="btn-ghost btn-sm">Filter</button><a href="{{ route('admin.orders.export', request()->query()) }}" class="btn-gold btn-sm">CSV</a></div>
    <div><label class="label" for="from">From</label><input id="from" type="date" name="from" class="input py-2" value="{{ $filters['from'] ?? '' }}"></div>
    <div><label class="label" for="to">To</label><input id="to" type="date" name="to" class="input py-2" value="{{ $filters['to'] ?? '' }}"></div>
</form>

<div class="card mt-6 overflow-x-auto">
    <table class="table-x">
        <thead><tr><th>Order</th><th>Placed</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
        <tbody>
        @forelse($orders as $o)
            <tr>
                <td><a class="font-medium text-gold hover:underline" href="{{ route('admin.orders.show', $o) }}">{{ $o->order_number }}</a></td>
                <td class="whitespace-nowrap text-xs">{{ $o->placed_at?->format('d M Y H:i') }}</td>
                <td><p>{{ $o->name }}</p><p class="text-xs text-muted">{{ $o->email }}</p></td>
                <td>{{ $o->items_count }}</td>
                <td>{{ money($o->grand_total) }}</td>
                <td><span class="{{ $o->payment_status === 'paid' ? 'badge-ok' : ($o->payment_status === 'failed' ? 'badge-bad' : 'badge-warn') }}">{{ $o->payment_method_label }} · {{ $o->payment_status_label }}</span></td>
                <td><span class="badge-muted">{{ $o->status_label }}</span></td>
                <td class="whitespace-nowrap text-right">
                    <a href="{{ route('admin.orders.show', $o) }}" class="btn-outline btn-sm">Manage</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-muted">No orders match these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $orders->links() }}</div>
@endsection