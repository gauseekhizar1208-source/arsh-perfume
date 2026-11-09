@extends('layouts.app')

@section('content')
{{-- Hero --}}
<section class="hero-lux relative isolate overflow-hidden border-b border-line">
    <div class="container-x grid min-h-[70vh] items-center gap-10 py-20 lg:grid-cols-2">
        <div class="animate-[fade-up_0.6s_ease-out_both]">
            <p class="hero-eyebrow mb-4 text-xs uppercase tracking-[0.35em] text-gold">Attar &amp; Perfume House</p>
            <h1 class="font-display text-4xl leading-tight tracking-[0.06em] sm:text-6xl">
                {{ setting('hero_heading') }}
            </h1>
            <p class="hero-sub mt-6 max-w-xl text-base leading-relaxed text-muted">{{ setting('hero_subheading') }}</p>
            <div class="mt-10 flex flex-wrap gap-4">
                <a href="{{ route('shop') }}" class="btn-gold">Shop Now</a>
                <a href="{{ route('collections.index') }}" class="btn-outline">Explore Collection</a>
            </div>
        </div>
        @if($heroBanners->isNotEmpty())
            @php($hero = $heroBanners->first())
            <a href="{{ $hero->link_url ?: route('shop') }}" class="card-hover block overflow-hidden">
                @if($hero->image_url)
                    <img src="{{ $hero->image_url }}" alt="{{ $hero->title }}" class="aspect-[4/3] w-full object-cover" width="800" height="600">
                @endif
                <div class="p-6">
                    <p class="font-display text-xl">{{ $hero->title }}</p>
                    @if($hero->subtitle)<p class="mt-2 text-sm text-muted">{{ $hero->subtitle }}</p>@endif
                </div>
            </a>
        @endif
    </div>
</section>

{{-- Promotional banners --}}
@foreach($promoBanners as $promo)
    <a href="{{ $promo->link_url ?: route('shop') }}" class="block border-b border-line bg-panel py-5 text-center transition hover:bg-gold/10">
        <span class="font-display tracking-widest">{{ $promo->title }}</span>
        @if($promo->subtitle)<span class="ml-3 text-sm text-muted">{{ $promo->subtitle }}</span>@endif
        @if($promo->button_text)<span class="ml-3 text-sm text-gold">{{ $promo->button_text }} &rarr;</span>@endif
    </a>
@endforeach

{{-- Categories --}}
@if(setting('home_show_categories') && $categories->isNotEmpty())
<section class="container-x py-20" aria-labelledby="cats-title">
    <header class="mb-10 text-center">
        <h2 id="cats-title" class="section-title">Shop by Category</h2>
        <span class="gold-rule"></span>
    </header>
    <ul class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
        @foreach($categories as $cat)
            <li>
                <a href="{{ route('collections.show', $cat) }}" class="card-hover flex h-full flex-col justify-end p-5 text-center">
                    <span class="font-display text-sm tracking-wide">{{ $cat->name }}</span>
                    <span class="mt-1 text-xs text-muted">{{ $cat->products_count }} {{ Str::plural('product', $cat->products_count) }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</section>
@endif

@foreach([
    ['home_show_featured', 'Featured Fragrances', $featured, 'shop?sort=popular'],
    ['home_show_bestsellers', 'Bestsellers', $bestsellers, 'shop?sort=popular'],
    ['home_show_new_arrivals', 'New Arrivals', $newArrivals, 'shop?sort=newest'],
] as [$flag, $title, $items, $more])
    @if(setting($flag) && $items->isNotEmpty())
    <section class="container-x py-16">
        <div class="mb-10 flex items-end justify-between gap-4">
            <h2 class="section-title">{{ $title }}</h2>
            <a href="{{ url($more) }}" class="text-sm text-gold hover:underline">View all</a>
        </div>
        <div class="grid grid-cols-2 gap-5 lg:grid-cols-4">
            @foreach($items as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    </section>
    @endif
@endforeach

{{-- Why choose us --}}
@if(setting('home_show_why_choose'))
<section class="border-y border-line bg-panel py-20">
    <div class="container-x grid gap-10 text-center md:grid-cols-3">
        @foreach([
            ['Quality you can smell', 'Every bottle is checked before it leaves our hands. We stand behind what we sell.'],
            ['Honest about every product', 'We show only the details we have confirmed: size, price, notes and stock.'],
            ['Delivered across India', 'Pay on delivery or online. Track your order from dispatch to doorstep.'],
        ] as [$h, $p])
            <div>
                <p class="font-display text-lg text-gold">{{ $h }}</p>
                <p class="mt-3 text-sm leading-relaxed text-muted">{{ $p }}</p>
            </div>
        @endforeach
    </div>
</section>
@endif

{{-- Reviews (approved only) --}}
@if(setting('home_show_reviews') && $reviews->isNotEmpty())
<section class="container-x py-20" aria-labelledby="reviews-title">
    <header class="mb-10 text-center">
        <h2 id="reviews-title" class="section-title">What Our Customers Say</h2>
        <span class="gold-rule"></span>
    </header>
    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        @foreach($reviews as $review)
            <figure class="card p-6">
                <p class="text-gold" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★', $review->rating) }}<span class="text-line">{{ str_repeat('★', 5 - $review->rating) }}</span></p>
                @if($review->title)<p class="mt-3 font-medium">{{ $review->title }}</p>@endif
                <blockquote class="mt-2 text-sm leading-relaxed text-muted">&ldquo;{{ $review->body }}&rdquo;</blockquote>
                <figcaption class="mt-4 text-xs text-muted">{{ $review->user->name }} &middot; on <a class="text-gold hover:underline" href="{{ route('product.show', $review->product) }}">{{ $review->product->name }}</a></figcaption>
            </figure>
        @endforeach
    </div>
</section>
@endif

{{-- Newsletter --}}
@if(setting('home_show_newsletter'))
<section class="container-x py-20">
    <div class="card mx-auto max-w-2xl p-8 text-center sm:p-12">
        <h2 class="section-title">Join the Arsh Circle</h2>
        <p class="mt-3 text-sm text-muted">New arrivals and offers, sent occasionally. Unsubscribe any time.</p>
        <form method="post" action="{{ route('newsletter.store') }}" class="mt-8 flex flex-col gap-4 text-left" x-data="{ ok: false }">
            @csrf
            <div class="flex flex-col gap-3 sm:flex-row">
                <label for="nl-email" class="sr-only">Email address</label>
                <input id="nl-email" name="email" type="email" required autocomplete="email" placeholder="you@example.com" class="input" value="{{ old('email') }}">
                <button class="btn-gold whitespace-nowrap">Subscribe</button>
            </div>
            <label class="flex items-start gap-3 text-xs text-muted">
                <input type="checkbox" name="consent" value="1" required class="mt-0.5 accent-[#C8A24D]" x-model="ok">
                <span>I agree to receive marketing emails from {{ setting('business_name') }}. I can unsubscribe at any time.</span>
            </label>
            @error('email')<p class="field-error">{{ $message }}</p>@enderror
        </form>
    </div>
</section>
@endif

{{-- Social --}}
@if(setting('home_show_social') && (setting('social_instagram') || setting('social_facebook') || setting('social_youtube') || setting('social_whatsapp')))
<section class="container-x pb-20 text-center">
    <h2 class="font-display text-xl tracking-widest">Follow Us</h2>
    <ul class="mt-5 flex flex-wrap justify-center gap-6 text-sm text-muted">
        @foreach(['social_instagram' => 'Instagram', 'social_facebook' => 'Facebook', 'social_youtube' => 'YouTube'] as $key => $label)
            @if(setting($key))<li><a class="hover:text-gold" href="{{ setting($key) }}" target="_blank" rel="noopener">{{ $label }}</a></li>@endif
        @endforeach
    </ul>
</section>
@endif
@endsection
