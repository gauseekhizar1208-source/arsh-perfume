@extends('account.layout')

@section('account')
<h1 class="section-title">My Orders</h1>
@if($orders->isEmpty())
    <p class="mt-6 text-sm text-muted">You have not placed any orders yet.</p>
@else
    <div class="card mt-6 overflow-x-auto">
        <table class="table-x">
            <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($orders as $o)
                <tr>
                    <td class="font-medium">{{ $o->order_number }}</td>
                    <td>{{ $o->placed_at?->format('d M Y') }}</td>
                    <td>{{ $o->items_count }}</td>
                    <td>{{ money($o->grand_total) }}</td>
                    <td><span class="badge-gold">{{ $o->status_label }}</span></td>
                    <td class="text-right"><a class="text-gold hover:underline" href="{{ route('account.orders.show', $o) }}">Details</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-8">{{ $orders->links() }}</div>
@endif
@endsection
