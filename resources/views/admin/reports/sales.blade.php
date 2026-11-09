@extends('layouts.admin')

@section('title', 'Sales report')
@section('heading', 'Sales report')

@section('content')
<form method="get" class="card flex flex-wrap items-end gap-4 p-5">
    <div><label class="label" for="from">From</label><input id="from" type="date" name="from" value="{{ $from }}" class="input py-2"></div>
    <div><label class="label" for="to">To</label><input id="to" type="date" name="to" value="{{ $to }}" class="input py-2"></div>
    <button class="btn-ghost btn-sm">Run</button>
    <a class="btn-gold btn-sm" href="{{ route('admin.reports.sales', ['from' => $from, 'to' => $to, 'format' => 'csv']) }}">Download CSV</a>
</form>

<div class="mt-6 grid gap-4 sm:grid-cols-2">
    <div class="card p-5"><p class="label">Net revenue in range</p><p class="font-display text-2xl text-gold">{{ money($revenue) }}</p></div>
    <div class="card p-5"><p class="label">Orders in range</p><p class="font-display text-2xl">{{ $rows->count() }}</p></div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <section class="card overflow-x-auto xl:col-span-2">
        <table class="table-x">
            <thead><tr><th>Order</th><th>Placed</th><th>Status</th><th>Payment</th><th class="text-right">Total</th><th class="text-right">Net</th></tr></thead>
            <tbody>
            @forelse($rows as $o)
                <tr><td>{{ $o->order_number }}</td><td class="text-xs">{{ $o->placed_at?->format('d M Y') }}</td><td>{{ $o->status_label }}</td><td>{{ $o->payment_status_label }}</td><td class="text-right">{{ money($o->grand_total) }}</td><td class="text-right">{{ money($o->net_revenue) }}</td></tr>
            @empty
                <tr><td colspan="6" class="text-muted">No orders in this range.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
    <section class="card p-5">
        <h2 class="font-display text-base">Bestsellers in range</h2>
        <ol class="mt-4 space-y-3 text-sm">
            @forelse($bestsellers as $b)<li class="flex justify-between gap-3"><span>{{ $b->product_name }}</span><span class="text-muted">{{ $b->quantity }} · {{ money($b->total) }}</span></li>@empty<li class="text-muted">None.</li>@endforelse
        </ol>
    </section>
</div>
<p class="mt-6 text-xs text-muted">Net revenue = paid orders (not cancelled or returned), minus refunds, counted on the order date.</p>
@endsection
