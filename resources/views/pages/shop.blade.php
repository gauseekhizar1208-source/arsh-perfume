@extends('layouts.app')

@section('title', 'Shop Attars & Perfumes | Arsh Perfume')
@section('meta_description', 'Shop roll-on attars, fragrance oils and perfumes from Arsh Perfume. Filter by category, price and fragrance family.')

@section('content')
<div class="container-x service-page">
    <x-page-heading title="Find your signature." eyebrow="ALL FRAGRANCES" description="Explore attars, fragrance oils and perfumes. Discover a scent that feels like you." />

    <div class="shop-layout grid gap-10 lg:grid-cols-[260px_1fr]">
        {{-- Filters: plain GET form so every filtered view is linkable and works without JavaScript --}}
        <form method="get" action="{{ route('shop') }}" class="card shop-filters h-fit space-y-6 p-6 lg:sticky lg:top-28" aria-label="Filter products">
            <h2>Refine your search</h2>
            <div>
                <label for="f-q" class="label">Search</label>
                <input id="f-q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Name or SKU" class="input">
            </div>
            <div>
                <label for="f-category" class="label">Category</label>
                <select id="f-category" name="category" class="input">
                    <option value="">All categories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->slug }}" @selected(($filters['category'] ?? '') === $c->slug)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="f-family" class="label">Fragrance family</label>
                <select id="f-family" name="family" class="input">
                    <option value="">All families</option>
                    @foreach($families as $f)
                        <option value="{{ $f }}" @selected(($filters['family'] ?? '') === $f)>{{ $f }}</option>
                    @endforeach
                </select>
            </div>
            <fieldset>
                <legend class="label">Price (₹)</legend>
                <div class="grid grid-cols-2 gap-2">
                    <input name="min" type="number" min="0" step="1" value="{{ $filters['min'] ?? '' }}" placeholder="Min" class="input" aria-label="Minimum price">
                    <input name="max" type="number" min="0" step="1" value="{{ $filters['max'] ?? '' }}" placeholder="Max" class="input" aria-label="Maximum price">
                </div>
            </fieldset>
            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" name="availability" value="in_stock" class="accent-[#C8A24D]" @checked(($filters['availability'] ?? '') === 'in_stock')>
                In stock only
            </label>
            <div>
                <label for="f-sort" class="label">Sort by</label>
                <select id="f-sort" name="sort" class="input">
                    <option value="newest" @selected(($filters['sort'] ?? '') === 'newest')>Newest</option>
                    <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Price: low to high</option>
                    <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Price: high to low</option>
                    <option value="popular" @selected(($filters['sort'] ?? '') === 'popular')>Popularity</option>
                </select>
            </div>
            <div class="flex gap-3">
                <button class="btn-gold flex-1 btn-sm">Apply</button>
                <a href="{{ route('shop') }}" class="btn-ghost btn-sm flex-1">Reset</a>
            </div>
        </form>

        <div class="shop-results">
            <div class="shop-results-bar"><p>{{ $products->total() }} {{ Str::plural('fragrance', $products->total()) }} found</p><a href="{{ route('collections.index') }}" class="text-gold hover:underline">Explore collections &rarr;</a></div>
            @if($products->isEmpty())
                <div class="card p-12 text-center">
                    <p class="font-display text-xl">No fragrances match these filters.</p>
                    <p class="mt-2 text-sm text-muted">Try clearing a filter or <a class="text-gold hover:underline" href="{{ route('shop') }}">see everything</a>.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-5 xl:grid-cols-3">
                    @foreach($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
                <div class="mt-12">{{ $products->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
