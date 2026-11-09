@extends('layouts.admin')

@section('title', 'Inventory')
@section('heading', 'Inventory')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4">
    <nav class="flex gap-2 text-sm" aria-label="Stock filter">
        @foreach(['all' => 'All', 'low' => 'Low stock', 'out' => 'Out of stock'] as $k => $label)
            <a href="{{ route('admin.inventory.index', ['stock' => $k]) }}" class="{{ $filter === $k ? 'btn-gold btn-sm' : 'btn-ghost btn-sm' }}">{{ $label }}</a>
        @endforeach
    </nav>
    <a href="{{ route('admin.inventory.movements') }}" class="text-sm text-gold hover:underline">Stock movement history</a>
</div>

<div class="card mt-6 overflow-x-auto">
    <table class="table-x">
        <thead><tr><th>Product</th><th>Size</th><th>SKU</th><th>Stock</th><th>Alert at</th><th>Adjust</th></tr></thead>
        <tbody>
        @forelse($variants as $v)
            <tr>
                <td>{{ $v->product?->name }} @if($v->product?->status !== 'active')<span class="badge-muted ml-1">{{ $v->product?->status }}</span>@endif</td>
                <td>{{ $v->name }}</td>
                <td class="font-mono text-xs">{{ $v->sku }}</td>
                <td class="{{ $v->stock <= 0 ? 'text-danger' : ($v->is_low_stock ? 'text-yellow-400' : '') }} font-medium">{{ $v->stock }}</td>
                <td>{{ $v->low_stock_threshold }}</td>
                <td>
                    <form method="post" action="{{ route('admin.inventory.adjust', $v) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <select name="mode" class="input w-24 py-2" aria-label="Adjustment type"><option value="add">Add</option><option value="remove">Remove</option><option value="set">Set to</option></select>
                        <input name="quantity" type="number" min="0" class="input w-20 py-2" required aria-label="Quantity">
                        <input name="reason" class="input w-48 py-2" placeholder="Reason (required)" required maxlength="200" aria-label="Reason">
                        <button class="btn-ghost btn-sm">Save</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted">Nothing to show.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $variants->links() }}</div>
@endsection
