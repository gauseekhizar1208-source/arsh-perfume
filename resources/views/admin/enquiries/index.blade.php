@extends('layouts.admin')

@section('title', 'Enquiries')
@section('heading', 'Contact enquiries')

@section('content')
<nav class="flex flex-wrap gap-2 text-sm" aria-label="Enquiry status">
    <a href="{{ route('admin.enquiries.index') }}" class="{{ $status === null ? 'btn-gold btn-sm' : 'btn-ghost btn-sm' }}">All</a>
    @foreach(\App\Models\ContactEnquiry::STATUSES as $k => $label)
        <a href="{{ route('admin.enquiries.index', ['status' => $k]) }}" class="{{ $status === $k ? 'btn-gold btn-sm' : 'btn-ghost btn-sm' }}">{{ $label }}</a>
    @endforeach
</nav>
<div class="card mt-6 overflow-x-auto">
    <table class="table-x">
        <thead><tr><th>Received</th><th>From</th><th>Subject</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($enquiries as $e)
            <tr>
                <td class="whitespace-nowrap text-xs">{{ $e->created_at->format('d M Y H:i') }}</td>
                <td>{{ $e->name }}<p class="text-xs text-muted">{{ $e->email }}</p></td>
                <td>{{ $e->subject ?: '—' }}</td>
                <td><span class="badge-muted">{{ \App\Models\ContactEnquiry::STATUSES[$e->status] ?? $e->status }}</span></td>
                <td class="text-right"><a class="text-gold hover:underline" href="{{ route('admin.enquiries.show', $e) }}">Open</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted">No enquiries.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $enquiries->links() }}</div>
@endsection
