@extends('layouts.admin')

@section('title', 'Policy & about pages')
@section('heading', 'Policy & about pages')

@section('content')
<p class="max-w-2xl text-sm text-muted">Every edit returns the page to draft. A page is shown on the website only after you review it and press Publish. Pages with no content cannot be published.</p>
<div class="card mt-6 overflow-x-auto">
    <table class="table-x">
        <thead><tr><th>Page</th><th>Public URL</th><th>Status</th><th>Reviewed</th><th></th></tr></thead>
        <tbody>
        @foreach($pages as $p)
            <tr>
                <td class="font-medium">{{ $p->title }}</td>
                <td class="text-xs text-muted">/{{ $p->slug }}</td>
                <td>{!! $p->is_published ? '<span class="badge-ok">Published</span>' : '<span class="badge-warn">Draft</span>' !!}</td>
                <td class="text-xs">{{ $p->reviewed_at?->format('d M Y H:i') ?? '—' }}</td>
                <td class="text-right"><a class="text-gold hover:underline" href="{{ route('admin.pages.edit', $p) }}">Edit</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
