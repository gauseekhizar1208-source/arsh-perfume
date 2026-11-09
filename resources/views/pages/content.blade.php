@extends('layouts.app')

@section('title', ($page->meta_title ?: $page->title).' | Arsh Perfume')
@section('meta_description', $page->meta_description ?: Str::limit(strip_tags((string) $page->content), 155))

@section('content')
<div class="container-x service-page">
    @if(isset($preview) && $preview)
        <div class="alert-info mb-6">Preview. This page is {{ $page->is_published ? 'published' : 'not published yet' }}.</div>
    @endif
    <x-page-heading :title="$page->title" :eyebrow="$page->slug === 'about' ? 'OUR STORY' : 'INFORMATION & CUSTOMER CARE'" />
    <div class="help-layout">
        <x-help-navigation />
        <article class="service-article">
        <div class="prose-arsh service-prose">
            {!! $page->content !!}
        </div>
        </article>
    </div>
</div>
@endsection
