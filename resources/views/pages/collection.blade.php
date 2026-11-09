@extends('layouts.app')

@section('title', $category->name.' | Arsh Perfume')
@section('meta_description', $category->description ?: 'Explore '.$category->name.' from Arsh Perfume.')

@section('content')
<div class="container-x py-12">
    <nav class="mb-6 text-xs uppercase tracking-widest text-muted"><a class="hover:text-gold" href="{{ route('collections.index') }}">Collections</a> / <span class="text-white">{{ $category->name }}</span></nav>
    <header class="catalogue-heading"><p class="eyebrow">THE COLLECTION</p><h1 class="section-title">{{ $category->name }}</h1>
    @if($category->description)<p class="mt-3 max-w-2xl text-sm text-muted">{{ $category->description }}</p>@endif</header>

    <div class="mt-10 grid grid-cols-2 gap-5 lg:grid-cols-4">
        @forelse($products as $product)<x-product-card :product="$product" />
        @empty<p class="col-span-full text-sm text-muted">No fragrances in this collection yet.</p>
        @endforelse
    </div>
    <div class="mt-12">{{ $products->links() }}</div>
</div>
@endsection
