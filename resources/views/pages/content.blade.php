@extends('layouts.app')

@section('title', ($page->meta_title ?: $page->title).' | Arsh Perfume')
@section('meta_description', $page->meta_description ?: Str::limit(strip_tags((string) $page->content), 155))

@section('content')
<div class="container-x service-page">
    @if(isset($preview) && $preview)
        <div class="alert-info mb-6">Preview. This page is {{ $page->is_published ? 'published' : 'not published yet' }}.</div>
    @endif
    <x-page-heading :title="$page->title" :eyebrow="$page->slug === 'about' ? 'OUR STORY' : 'INFORMATION & CUSTOMER CARE'" :description="$page->meta_description" />
    <div class="help-layout">
        <x-help-navigation />
        <article class="service-article">
        @if($page->slug !== 'about')
            <p class="mb-6 text-xs text-muted">Last updated {{ $page->updated_at->format('d F Y') }}</p>
        @endif
        <div class="prose-arsh service-prose">
            {!! $page->content !!}
        </div>
        <div class="service-callout"><div><h2>How can we help?</h2><p>For questions about this page or your order, get in touch.</p></div><a href="{{ route('contact') }}" class="btn-ghost">Contact us &rarr;</a></div>
        </article>
    </div>
</div>
@endsection
