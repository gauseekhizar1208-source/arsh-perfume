@extends('layouts.admin')

@section('title', 'Coupons')
@section('heading', 'Coupons')

@section('content')
<div class="flex justify-end"><a href="{{ route('admin.coupons.create') }}" class="btn-gold btn-sm">New coupon</a></div>
<div class="card mt-6 overflow-x-auto">
    <table class="table-x">
        <thead><tr><th>Code</th><th>Discount</th><th>Min order</th><th>Valid</th><th>Used</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($coupons as $c)
            <tr>
                <td class="font-mono font-medium">{{ $c->code }}</td>
                <td>{{ $c->type === 'percent' ? rtrim(rtrim($c->value, '0'), '.').'%' : money($c->value) }} @if($c->max_discount) <span class="text-xs text-muted">(max {{ money($c->max_discount) }})</span>@endif</td>
                <td>{{ $c->min_order_amount ? money($c->min_order_amount) : '—' }}</td>
                <td class="text-xs">{{ $c->starts_at?->format('d M Y') ?? 'Now' }} → {{ $c->expires_at?->format('d M Y') ?? 'No expiry' }}</td>
                <td>{{ $c->used_count }}{{ $c->usage_limit ? ' / '.$c->usage_limit : '' }}</td>
                <td>{!! $c->is_active ? '<span class="badge-ok">Active</span>' : '<span class="badge-muted">Inactive</span>' !!}</td>
                <td class="whitespace-nowrap text-right">
                    <a class="text-gold hover:underline" href="{{ route('admin.coupons.edit', $c) }}">Edit</a>
                    <form method="post" action="{{ route('admin.coupons.destroy', $c) }}" class="inline" onsubmit="return confirm('Delete or deactivate this coupon?')">@csrf @method('DELETE')<button class="ml-3 text-muted hover:text-danger">Delete</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-muted">No coupons yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $coupons->links() }}</div>
@endsection
