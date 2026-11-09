@extends('layouts.admin')

@section('title', 'Customers report')
@section('heading', 'Customers report')

@section('content')
<div class="flex justify-end"><a class="btn-gold btn-sm" href="{{ route('admin.reports.customers', ['format' => 'csv']) }}">Download CSV</a></div>
<div class="card mt-6 overflow-x-auto">
    <table class="table-x">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Joined</th></tr></thead>
        <tbody>
        @forelse($rows as $c)
            <tr><td>{{ $c->name }}</td><td>{{ $c->email }}</td><td>{{ $c->phone ?? '—' }}</td><td>{{ $c->orders_count }}</td><td class="text-xs">{{ $c->created_at->format('d M Y') }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-muted">No customers yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
