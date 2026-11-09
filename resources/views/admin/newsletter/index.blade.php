@extends('layouts.admin')

@section('title', 'Newsletter')
@section('heading', 'Newsletter subscribers')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4">
    <p class="text-sm text-muted">{{ $activeCount }} active subscribers. Each record keeps its consent time and IP address.</p>
    <a href="{{ route('admin.newsletter.export') }}" class="btn-gold btn-sm">Export active (CSV)</a>
</div>
<div class="card mt-6 overflow-x-auto">
    <table class="table-x">
        <thead><tr><th>Email</th><th>Consent given</th><th>Consent IP</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($subscribers as $s)
            <tr>
                <td>{{ $s->email }}</td>
                <td class="text-xs">{{ $s->consent_given_at?->format('d M Y H:i') }}</td>
                <td class="text-xs">{{ $s->consent_ip ?? '—' }}</td>
                <td>{!! $s->unsubscribed_at ? '<span class="badge-muted">Unsubscribed</span>' : '<span class="badge-ok">Subscribed</span>' !!}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-muted">No subscribers yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $subscribers->links() }}</div>
@endsection
