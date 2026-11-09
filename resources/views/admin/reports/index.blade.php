@extends('layouts.admin')

@section('title', 'Reports')
@section('heading', 'Reports')

@section('content')
<div class="grid gap-6 md:grid-cols-3">
    <a href="{{ route('admin.reports.sales') }}" class="card-hover block p-6"><p class="font-display text-lg">Sales report</p><p class="mt-2 text-sm text-muted">Orders and net revenue for a date range, with bestsellers. CSV export.</p></a>
    <a href="{{ route('admin.reports.inventory') }}" class="card-hover block p-6"><p class="font-display text-lg">Inventory report</p><p class="mt-2 text-sm text-muted">Stock level for every size against its alert threshold. CSV export.</p></a>
    <a href="{{ route('admin.reports.customers') }}" class="card-hover block p-6"><p class="font-display text-lg">Customers</p><p class="mt-2 text-sm text-muted">Registered customers and their order counts. CSV export (no passwords).</p></a>
</div>
@endsection
