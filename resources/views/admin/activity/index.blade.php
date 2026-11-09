@extends('layouts.admin')

@section('title', 'Activity log')
@section('heading', 'Activity log')

@section('content')
<div class="card overflow-x-auto">
    <table class="table-x">
        <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
        <tbody>
        @forelse($logs as $l)
            <tr>
                <td class="whitespace-nowrap text-xs">{{ $l->created_at->format('d M Y H:i:s') }}</td>
                <td>{{ $l->user?->name ?? 'System' }}<p class="text-xs text-muted">{{ $l->user?->email }}</p></td>
                <td class="font-mono text-xs">{{ $l->action }}</td>
                <td>{{ $l->description }}</td>
                <td class="font-mono text-xs">{{ $l->ip_address }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted">No activity yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $logs->links() }}</div>
@endsection
