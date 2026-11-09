@extends('layouts.admin')

@section('title', 'Edit '.$page->title)
@section('heading', $page->title)

@section('content')
<div class="grid gap-6 xl:grid-cols-3">
    <form method="post" action="{{ route('admin.pages.update', $page) }}" class="card space-y-5 p-6 xl:col-span-2">
        @csrf @method('PUT')
        <div><label class="label" for="title">Title</label><input id="title" name="title" class="input" value="{{ old('title', $page->title) }}" required></div>
        <div>
            <label class="label" for="content">Content (HTML: p, h2, h3, ul, ol, li, strong, em, a, br)</label>
            <textarea id="content" name="content" rows="22" class="input font-mono text-xs">{{ old('content', $page->content) }}</textarea>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label class="label" for="meta_title">SEO title</label><input id="meta_title" name="meta_title" class="input" maxlength="70" value="{{ old('meta_title', $page->meta_title) }}"></div>
            <div><label class="label" for="meta_description">SEO description</label><input id="meta_description" name="meta_description" class="input" maxlength="160" value="{{ old('meta_description', $page->meta_description) }}"></div>
        </div>
        <button class="btn-gold">Save as draft</button>
    </form>

    <aside class="card h-fit space-y-4 p-6 text-sm">
        <p class="font-display text-base">Publishing</p>
        <p class="text-muted">Status: {!! $page->is_published ? '<span class="text-ok">Published</span>' : '<span class="text-yellow-400">Draft</span>' !!}</p>
        @if($page->is_published)
            <form method="post" action="{{ route('admin.pages.unpublish', $page) }}">@csrf<button class="btn-ghost w-full">Unpublish</button></form>
        @else
            <form method="post" action="{{ route('admin.pages.publish', $page) }}" onsubmit="return confirm('Confirm you have reviewed this text and it is ready to publish.')">@csrf<button class="btn-gold w-full">I have reviewed it - Publish</button></form>
        @endif
        <a href="{{ route('admin.pages.preview', $page) }}" target="_blank" rel="noopener" class="btn-ghost w-full">Preview</a>
        <p class="text-xs text-muted">Note: policy text is a template. The owner must review it for accuracy before publishing.</p>
    </aside>
</div>
@endsection
