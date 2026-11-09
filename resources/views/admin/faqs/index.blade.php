@extends('layouts.admin')

@section('title', 'FAQs')
@section('heading', 'FAQs')

@section('content')
<div class="flex justify-end"><a href="{{ route('admin.faqs.create') }}" class="btn-gold btn-sm">New FAQ</a></div>
<div class="card mt-6 divide-y divide-line">
    @forelse($faqs as $f)
        <div class="flex flex-wrap items-start justify-between gap-4 p-5 text-sm">
            <div class="min-w-0 flex-1">
                <p class="font-medium">{{ $f->question }} @if(! $f->is_active)<span class="badge-muted ml-2">Hidden</span>@endif</p>
                <p class="mt-1 line-clamp-2 text-muted">{{ $f->answer }}</p>
            </div>
            <div class="flex gap-4 text-xs">
                <a class="text-gold hover:underline" href="{{ route('admin.faqs.edit', $f) }}">Edit</a>
                <form method="post" action="{{ route('admin.faqs.destroy', $f) }}" onsubmit="return confirm('Delete this FAQ?')">@csrf @method('DELETE')<button class="text-muted hover:text-danger">Delete</button></form>
            </div>
        </div>
    @empty
        <p class="p-5 text-sm text-muted">No FAQs yet.</p>
    @endforelse
</div>
@endsection
