@extends('layouts.admin')

@section('title', 'Stock movements')
@section('heading', 'Stock movements')

@section('content')
<div class="card overflow-x-auto">
    <table class="table-x">
        <thead><tr><th>When</th><th>Product / size</th><th>Type</th><th>Change</th><th>Stock after</th><th>Reference</th><th>By</th><th>Note</th></tr></thead>
        <tbody>
        @forelse($movements as $m)
            <tr>
                <td class="whitespace-nowrap text-xs">{{ $m->created_at->format('d M Y H:i') }}</td>
                <td>{{ $m->variant?->product?->name }} <span class="text-muted">({{ $m->variant?->name }})</span></td>
                <td>{{ \App\Models\InventoryMovement::TYPES[$m->type] ?? $m->type }}</td>
                <td class="{{ $m->quantity_change < 0 ? 'text-danger' : 'text-ok' }}">{{ $m->quantity_change > 0 ? '+' : '' }}{{ $m->quantity_change }}</td>
                <td>{{ $m->stock_after }}</td>
                <td class="text-xs">{{ $m->reference_type ? $m->reference_type.' #'.$m->reference_id : '—' }}</td>
                <td class="text-xs">{{ $m->user?->name ?? 'System' }}</td>
                <td class="text-xs text-muted">{{ $m->note }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-muted">No movements recorded yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $movements->links() }}</div>
@endsection
