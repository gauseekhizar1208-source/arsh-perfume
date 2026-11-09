@extends('layouts.app')
@section('title', 'Arsh Perfume | A Scent to Remember')
@section('content')
<section class="atelier-hero" aria-labelledby="hero-title">
    <div class="container-x hero-grid">
        <div class="hero-copy">
            <p class="eyebrow"><span></span> A WORLD BEYOND THE ORDINARY</p>
            <h1 id="hero-title">Feel The<br><em>Best Quality.</em></h1>
            <p class="hero-description">{{ setting('hero_subheading', 'Discover attars, perfumes and fragrance oils that turn everyday moments into lasting memories.') }}</p>
            <div class="hero-actions"><a href="{{ route('shop') }}" class="btn-gold">Discover your scent <span aria-hidden="true">&nearr;</span></a><a href="{{ route('collections.index') }}" class="text-link">The collections <span aria-hidden="true">&rarr;</span></a></div>
            <div class="hero-footnote"><span class="hero-seal" aria-hidden="true">A</span><p>Personal by nature.<br><strong>Unforgettable by Arsh.</strong></p><span class="hero-footnote-rule"></span><span class="hero-footnote-small">ATTARS &middot; PERFUMES<br>FRAGRANCE OILS</span></div>
        </div>
        <div class="hero-art">
            @php($hero = $heroBanners->first())
            @php($spotlight = $featured->first() ?? $bestsellers->first() ?? $newArrivals->first())
            <span class="scene-watermark" aria-hidden="true">ARSH</span>
            <x-fragrance-sculpture />
        </div>
    </div>
</section>
@if($hero || $spotlight)
<div class="spotlight-bar container-x"><span class="spotlight-kicker">IN THE SPOTLIGHT <span aria-hidden="true">&mdash;</span></span><a href="{{ $hero?->link_url ?: ($spotlight ? route('product.show', $spotlight) : route('shop')) }}">@if($hero?->image_url || $spotlight?->has_image)<img src="{{ $hero?->image_url ?: $spotlight->primary_image_url }}" width="48" height="48" alt="">@endif<span>{{ $hero?->title ?: $spotlight?->name }}</span><span class="spotlight-explore">Explore <span aria-hidden="true">&nearr;</span></span></a><span class="spotlight-aside">A fragrance is a feeling, bottled.</span></div>
@endif
<div class="ritual-strip"><span>A fragrance for every version of you</span><span aria-hidden="true">&diams;</span><span>Discover your signature</span><span aria-hidden="true">&diams;</span><span>Delivered across India</span></div>
@foreach($promoBanners as $promo)
<a class="promo-strip" href="{{ $promo->link_url ?: route('shop') }}">{{ $promo->title }} @if($promo->subtitle)<span>{{ $promo->subtitle }}</span>@endif <span aria-hidden="true">&rarr;</span></a>
@endforeach
@if(setting('home_show_categories') && $categories->isNotEmpty())
<section class="container-x section-space" aria-labelledby="collections-title">
    <div class="section-heading"><div><p class="eyebrow">FOLLOW YOUR SENSES</p><h2 id="collections-title" class="section-title">Find your kind of <em>fragrance.</em></h2></div><a class="text-link" href="{{ route('collections.index') }}">All collections &nearr;</a></div>
    <div class="collection-grid">
        @foreach($categories->take(3) as $cat)
        <x-collection-card :category="$cat" :number="$loop->iteration" />
        @endforeach
    </div>
</section>
@endif
@php($shownProductIds = collect())
@foreach([
    ['home_show_featured', 'THE SIGNATURE EDIT', 'A lasting impression.', $featured, 'popular'],
    ['home_show_bestsellers', 'THE MOST LOVED', 'Your next favourite.', $bestsellers, 'popular'],
    ['home_show_new_arrivals', 'SOMETHING NEW', 'Fresh discoveries.', $newArrivals, 'newest'],
] as [$flag, $eyebrow, $title, $items, $sort])
@php($selection = $items->reject(fn ($item) => $shownProductIds->contains($item->id))->take(4))
@if(setting($flag) && $selection->isNotEmpty())
<section class="container-x section-space" aria-label="{{ $title }}">
    <div class="section-heading"><div><p class="eyebrow">{{ $eyebrow }}</p><h2 class="section-title">{{ $title }}</h2></div><a href="{{ route('shop', ['sort' => $sort]) }}" class="text-link">Shop the edit &nearr;</a></div>
    <div class="home-product-grid grid grid-cols-2 gap-4 md:gap-7 lg:grid-cols-4">@foreach($selection as $product)<x-product-card :product="$product" />@endforeach</div>
</section>
@php($shownProductIds = $shownProductIds->merge($selection->pluck('id')))
@endif
@endforeach
@if(setting('home_show_why_choose'))
<section class="house-story"><div class="container-x story-grid"><div><p class="eyebrow">THE HOUSE OF ARSH</p><h2>A small ritual.<br><em>An extraordinary feeling.</em></h2></div><div><p>Some things are felt before they are seen. The warmth of an attar. A familiar fragrance. A memory that stays.</p><p>Explore our world of scent and find the one that feels like you.</p><a class="text-link" href="{{ route('pages.about') }}">Get to know Arsh &nearr;</a></div></div></section>
<div class="container-x service-grid">@foreach([['01', 'Care in every bottle', 'Each bottle is checked before it leaves our hands.'], ['02', 'Your scent, your way', 'Explore fragrance families, sizes and notes at your own pace.'], ['03', 'From us to your doorstep', 'Delivery across India, with order tracking along the way.']] as [$number, $title, $copy])<div><span>{{ $number }}</span><h3>{{ $title }}</h3><p>{{ $copy }}</p></div>@endforeach</div>
@endif
@if(setting('home_show_reviews') && $reviews->isNotEmpty())
<section class="container-x section-space"><div class="section-heading"><div><p class="eyebrow">SCENT STORIES</p><h2 class="section-title">In their own words.</h2></div></div><div class="grid gap-6 md:grid-cols-3">@foreach($reviews->take(3) as $review)<figure class="review-card"><p class="review-rating" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★', $review->rating) }}</p><blockquote>&ldquo;{{ $review->body }}&rdquo;</blockquote><figcaption>{{ $review->user->name }}<a href="{{ route('product.show', $review->product) }}">{{ $review->product->name }}</a></figcaption></figure>@endforeach</div></section>
@endif
@if(setting('home_show_newsletter'))
<section class="newsletter-section"><div class="container-x newsletter-grid"><div><p class="eyebrow">LET'S STAY CLOSE</p><h2 class="section-title">A little note from <em>Arsh.</em></h2><p>New discoveries, fragrance stories and occasional offers.</p></div><form method="post" action="{{ route('newsletter.store') }}">@csrf<label for="nl-email" class="label">Your email address</label><div class="newsletter-input"><input id="nl-email" class="input" name="email" type="email" autocomplete="email" placeholder="you@example.com" required value="{{ old('email') }}"><button class="btn-gold" type="submit">Join the circle &rarr;</button></div><label class="consent-label"><input type="checkbox" name="consent" value="1" required><span>I agree to receive marketing emails from {{ setting('business_name', 'Arsh Perfume') }}. Unsubscribe at any time.</span></label>@error('email')<p class="field-error">{{ $message }}</p>@enderror</form></div></section>
@endif
@endsection
