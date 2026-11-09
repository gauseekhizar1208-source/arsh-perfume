@extends('layouts.admin')

@section('title', 'Reviews')
@section('heading', 'Product reviews')

@section('content')
<nav class="flex gap-2 text-sm" aria-label="Review status">
    @foreach(['all' => 'All reviews', 'pending' => 'Awaiting ', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $label)
        <a href="{{ route('admin.reviews.index', ['status' => $k]) }}" class="{{ $status === $k ? 'btn-gold btn-sm' : 'btn-ghost btn-sm' }}">{{ $label }}</a>
    @endforeach
</nav>

<div class="mt-6 space-y-4">
    @forelse($reviews as $r)
        <article class="card p-5 text-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span class="text-gold">{{ str_repeat('★', $r->rating) }}</span>
                    <span class="font-medium">{{ $r->title }}</span>
                    <span class="text-muted">on {{ $r->product?->name }} by {{ $r->user?->name }}</span>
                    @if($r->status === 'approved')
                        <span class="badge-ok">Approved</span>
                    @elseif($r->status === 'rejected')
                        <span class="badge-bad">Rejected</span>
                    @else
                        <span class="badge-warn">Awaiting</span>
                    @endif
                </p>
                <div class="flex gap-2">
                    @if($r->status !== 'approved')<form method="post" action="{{ route('admin.reviews.approve', $r) }}">@csrf @method('PATCH')<button class="btn-ghost btn-sm">Approve</button></form>@endif
                    @if($r->status !== 'rejected')<form method="post" action="{{ route('admin.reviews.reject', $r) }}">@csrf @method('PATCH')<button class="btn-ghost btn-sm">Reject</button></form>@endif
                    <form method="post" action="{{ route('admin.reviews.destroy', $r) }}" onsubmit="return confirm('Delete this review permanently?')">@csrf @method('DELETE')<button class="btn-danger btn-sm">Delete</button></form>
                </div>
            </div>
            <p class="mt-3 text-muted">{{ $r->body }}</p>
        </article>
    @empty
        <p class="text-sm text-muted">No reviews here yet.</p>
    @endforelse
</div>
<div class="mt-6">{{ $reviews->links() }}</div>
@endsection