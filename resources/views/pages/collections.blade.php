@extends('layouts.app')

@section('title', 'Collections | Arsh Perfume')

@section('content')
<div class="container-x py-12">
    <header class="catalogue-heading"><p class="eyebrow">FOLLOW YOUR SENSES</p><h1 class="section-title">A world of <em>discovery.</em></h1>
    <p class="mt-3 text-sm text-muted">Find a fragrance that speaks your language.</p></header>
    <div class="collection-grid mt-10">
        @forelse($categories as $cat)
            <x-collection-card :category="$cat" :number="$loop->iteration" />
        @empty
            <p class="text-sm text-muted">Collections are coming soon.</p>
        @endforelse
    </div>
</div>
@endsection
