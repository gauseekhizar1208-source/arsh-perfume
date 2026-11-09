@extends('layouts.admin')

@section('title', 'Customers')
@section('heading', 'Customers')

@section('content')
<form method="get" class="flex flex-wrap items-end gap-3">
    <div><label class="label" for="q">Search</label><input id="q" name="q" class="input py-2" value="{{ $q }}" placeholder="Name, email or phone"></div>
    <button class="btn-ghost btn-sm">Search</button>
</form>

<div class="card mt-6 overflow-x-auto">
    <table class="table-x">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Joined</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($customers as $c)
            <tr>
                <td class="font-medium">{{ $c->name }}</td>
                <td>{{ $c->email }}</td>
                <td>{{ $c->phone ?? '—' }}</td>
                <td>{{ $c->orders_count }}</td>
                <td class="text-xs">{{ $c->created_at->format('d M Y') }}</td>
                <td>{!! $c->is_active ? '<span class="badge-ok">Active</span>' : '<span class="badge-bad">Disabled</span>' !!}</td>
                <td class="text-right"><a class="text-gold hover:underline" href="{{ route('admin.customers.show', $c) }}">Open</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-muted">No customers found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $customers->links() }}</div>
@endsection
