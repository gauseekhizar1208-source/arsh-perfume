@extends('layouts.admin')

@section('title', 'Categories')
@section('heading', 'Categories')

@section('content')
<div class="flex items-center justify-between">
    <p class="text-sm text-muted">Drag rows or use the arrows to reorder. Changes save together.</p>
    <a href="{{ route('admin.categories.create') }}" class="btn-gold btn-sm">New category</a>
</div>

<form method="post" action="{{ route('admin.categories.reorder') }}" x-data="{ ids: @js($categories->pluck('id')->values()) }" class="card overflow-x-auto">
    @csrf
    <table class="table-x">
        <thead><tr><th>Order</th><th>Name</th><th>Products</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
        <tbody>
        @forelse($categories as $c)
            <tr>
                <td>
                    <input type="hidden" name="order[]" :value="{{ $c->id }}">
                    <span class="font-mono text-muted">{{ $loop->iteration }}</span>
                </td>
                <td class="font-medium">{{ $c->name }} <span class="block text-xs text-muted">/collections/{{ $c->slug }}</span></td>
                <td>{{ $c->products_count }}</td>
                <td>@if($c->is_active)<span class="badge-ok">Active</span>@else<span class="badge-muted">Inactive</span>@endif</td>
                <td class="whitespace-nowrap text-right">
                    <a class="text-gold hover:underline" href="{{ route('admin.categories.edit', $c) }}">Edit</a>
                    <a class="ml-3 text-muted hover:text-white" href="{{ route('collections.show', $c) }}" target="_blank" rel="noopener">View</a>
                    <button type="submit" form="delete-category-{{ $c->id }}" class="ml-3 text-danger hover:underline">Delete</button>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted">No categories yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    @if($categories->isNotEmpty())
        <div class="flex justify-end border-t border-line p-4"><button class="btn-ghost btn-sm">Save order</button></div>
    @endif
</form>

{{-- Delete forms live outside the reorder form (nested forms are invalid HTML). Buttons above point to them via form="..." --}}
@foreach($categories as $c)
    <form id="delete-category-{{ $c->id }}" method="post" action="{{ route('admin.categories.destroy', $c) }}"
          onsubmit="return confirm('Delete &quot;{{ addslashes($c->name) }}&quot;? This cannot be undone.')" class="hidden">
        @csrf
        @method('DELETE')
    </form>
@endforeach

@if($categories->isNotEmpty())
<div class="card p-5 text-xs text-muted">
    Quick actions: @foreach($categories as $c)
        <form method="post" action="{{ route('admin.categories.toggle', $c) }}" class="inline">@csrf @method('PATCH')<button class="mr-4 hover:text-gold">{{ $c->is_active ? 'Deactivate' : 'Activate' }} {{ $c->name }}</button></form>
    @endforeach
</div>
@endif
@endsection