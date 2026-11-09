@extends('layouts.admin')

@section('title', 'Inventory report')
@section('heading', 'Inventory report')

@section('content')
<div class="flex justify-end"><a class="btn-gold btn-sm" href="{{ route('admin.reports.inventory', ['format' => 'csv']) }}">Download CSV</a></div>
<div class="card mt-6 overflow-x-auto">
    <table class="table-x">
        <thead><tr><th>Product</th><th>Status</th><th>Size</th><th>SKU</th><th>Stock</th><th>Alert at</th><th>Price</th></tr></thead>
        <tbody>
        @forelse($rows as $v)
            <tr>
                <td>{{ $v->product?->name }}</td>
                <td>{{ $v->product?->status }}</td>
                <td>{{ $v->name }}</td>
                <td class="font-mono text-xs">{{ $v->sku }}</td>
                <td class="{{ $v->stock <= 0 ? 'text-danger' : ($v->is_low_stock ? 'text-yellow-400' : '') }}">{{ $v->stock }}</td>
                <td>{{ $v->low_stock_threshold }}</td>
                <td>{{ $v->price ? money($v->price) : 'not set' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-muted">No variants yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
