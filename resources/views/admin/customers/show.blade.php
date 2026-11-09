@extends('layouts.admin')

@section('title', $customer->name)
@section('heading', $customer->name)

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <section class="card space-y-3 p-6 text-sm">
        <p class="font-display text-lg">{{ $customer->name }}</p>
        <p><a class="text-gold hover:underline" href="mailto:{{ $customer->email }}">{{ $customer->email }}</a></p>
        <p>{{ $customer->phone ?? 'No phone' }}</p>
        <p class="text-muted">Joined {{ $customer->created_at->format('d M Y') }} · Last login {{ $customer->last_login_at?->format('d M Y H:i') ?? 'never' }}</p>
        <div class="border-t border-line pt-4">
            <p class="label">Lifetime purchases</p>
            <p class="font-display text-2xl text-gold">{{ money($lifetime) }}</p>
        </div>
        <form method="post" action="{{ route('admin.customers.toggle', $customer) }}" class="pt-2" onsubmit="return confirm('Change this account status?')">
            @csrf @method('PATCH')
            <button class="{{ $customer->is_active ? 'btn-danger' : 'btn-ghost' }} w-full btn-sm">{{ $customer->is_active ? 'Disable account' : 'Reactivate account' }}</button>
        </form>
    </section>

    <section class="card p-6 text-sm lg:col-span-2" aria-labelledby="addr-h">
        <h2 id="addr-h" class="font-display text-lg">Addresses</h2>
        <ul class="mt-4 space-y-3">
            @forelse($customer->addresses as $a)
                <li class="border border-line p-3"><span class="font-medium">{{ $a->label ?: 'Address' }}</span> <span class="text-muted">{{ $a->oneLine() }}</span></li>
            @empty
                <li class="text-muted">No saved addresses.</li>
            @endforelse
        </ul>
    </section>

    <section class="card overflow-x-auto lg:col-span-3" aria-labelledby="ord-h">
        <h2 id="ord-h" class="border-b border-line px-5 py-4 font-display text-lg">Orders</h2>
        <table class="table-x">
            <thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($orders as $o)
                <tr>
                    <td><a class="text-gold hover:underline" href="{{ route('admin.orders.show', $o) }}">{{ $o->order_number }}</a></td>
                    <td>{{ $o->placed_at?->format('d M Y') }}</td>
                    <td>{{ money($o->grand_total) }}</td>
                    <td>{{ $o->payment_method_label }} · {{ $o->payment_status_label }}</td>
                    <td>{{ $o->status_label }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No orders yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $orders->links() }}</div>
    </section>
</div>
@endsection
