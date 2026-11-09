@extends('layouts.admin')

@section('title', 'Products')
@section('heading', 'Products')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4">
    <form method="get" class="flex flex-wrap items-end gap-3">
        <div><label class="label" for="q">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input py-2" placeholder="Name or SKU"></div>
        <div><label class="label" for="status">Status</label>
            <select id="status" name="status" class="input py-2"><option value="">All</option>
                @foreach(['draft', 'active', 'archived'] as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>@endforeach
            </select></div>
        <div><label class="label" for="category">Category</label>
            <select id="category" name="category" class="input py-2"><option value="">All</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" @selected((string) ($filters['category'] ?? '') === (string) $c->id)>{{ $c->name }}</option>@endforeach
            </select></div>
        <button class="btn-ghost btn-sm">Filter</button>
    </form>
    <div class="flex gap-3">
        <a href="{{ route('admin.products.export') }}" class="btn-ghost btn-sm">Export CSV</a>
        <a href="{{ route('admin.products.create') }}" class="btn-gold btn-sm">New product</a>
    </div>
</div>

<div class="card mt-6 overflow-x-auto">
    <table class="table-x">
        <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Sizes &amp; prices</th><th>Stock</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
        <tbody>
        @forelse($products as $p)
            <tr>
                <td>
                    <div class="flex items-center gap-3">
                        <img src="{{ $p->primary_image_url }}" alt="" class="h-12 w-12 rounded-sm border border-line object-cover" loading="lazy">
                        <div>
                            <p class="font-medium">{{ $p->name }}</p>
                            <p class="text-xs text-muted">{{ $p->is_featured ? 'Featured' : '' }} {{ $p->is_bestseller ? 'Bestseller' : '' }}</p>
                        </div>
                    </div>
                </td>
                <td class="font-mono text-xs">{{ $p->sku }}</td>
                <td>{{ $p->category?->name ?? '—' }}</td>
                <td class="text-xs">
                    @foreach($p->variants as $v)
                        <div>{{ $v->name }}: {{ $v->price ? money($v->price) : 'no price' }}{{ $v->is_active ? '' : ' (inactive)' }}</div>
                    @endforeach
                </td>
                <td>{{ $p->variants->sum('stock') }}</td>
                <td>
                    @if($p->status === 'active')<span class="badge-ok">Active</span>
                    @elseif($p->status === 'draft')<span class="badge-warn">Draft</span>
                    @else<span class="badge-muted">Archived</span>@endif
                </td>
                <td class="whitespace-nowrap text-right">
                    <a class="text-gold hover:underline" href="{{ route('admin.products.edit', $p) }}">Edit</a>
                    <form method="post" action="{{ route('admin.products.destroy', $p) }}" class="inline" onsubmit="return confirm('Delete or archive this product?')">
                        @csrf @method('DELETE')
                        <button class="ml-3 text-muted hover:text-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-muted">No products yet. <a class="text-gold hover:underline" href="{{ route('admin.products.create') }}">Add the first one</a>.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $products->links() }}</div>
@endsection
