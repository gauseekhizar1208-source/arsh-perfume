@extends('layouts.app')

@php
    $primary = $product->primary_image_url;
    $seoTitle = $product->meta_title ?: $product->name.' | Arsh Perfume';
    $seoDesc = $product->meta_description ?: ($product->short_description ?: Str::limit(strip_tags((string) $product->description), 155));
    $initial = $variants->first()?->id;
    $structured = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'sku' => $product->sku,
        'description' => $seoDesc,
        'image' => $product->images->map->url->values()->all(),
        'brand' => ['@type' => 'Brand', 'name' => setting('business_name', 'Arsh Perfume')],
        'offers' => $variants->map(fn ($v) => [
            '@type' => 'Offer',
            'sku' => $v->sku,
            'price' => $v->effective_price,
            'priceCurrency' => 'INR',
            'availability' => $v->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => route('product.show', $product),
        ])->values()->all(),
    ];
    if ($reviews->isNotEmpty() && $average) {
        $structured['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => $average, 'reviewCount' => $reviews->count()];
    }
@endphp

@section('title', $seoTitle)
@section('meta_description', $seoDesc)
@section('og_type', 'product')
@if($primary) @section('og_image', $primary) @endif

@push('head')
    <script type="application/ld+json">{!! json_encode($structured, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<div class="container-x py-12">
    <nav aria-label="Breadcrumb" class="mb-8 text-xs uppercase tracking-widest text-muted">
        <a href="{{ route('home') }}" class="hover:text-gold">Home</a> /
        <a href="{{ route('shop') }}" class="hover:text-gold">Shop</a>
        @if($product->category) / <a href="{{ route('collections.show', $product->category) }}" class="hover:text-gold">{{ $product->category->name }}</a>@endif
        / <span class="text-white">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-12 lg:grid-cols-2"
         x-data="{
            variants: @js($variants->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'sku' => $v->sku, 'price' => $v->effective_price, 'list' => $v->price, 'sale' => $v->is_on_sale, 'stock' => $v->stock, 'label' => $v->stock_label, 'max' => min($v->stock, 10)])->values()),
            selected: {{ (int) $initial }},
            qty: 1,
            get current() { return this.variants.find(v => v.id === this.selected) ?? this.variants[0]; },
            get maxQty() { return Math.max(1, this.current?.max ?? 1); }
         }">
        {{-- Gallery --}}
        <div x-data="{ active: 0 }">
            <div class="card overflow-hidden bg-panel">
                @forelse($product->images as $i => $img)
                    <img x-show="active === {{ $i }}" x-cloak src="{{ $img->url }}" alt="{{ $img->alt ?: $product->name }}"
                         class="block h-auto w-full" width="800" height="800" @if($i > 0) loading="lazy" @endif>
                @empty
                    <div class="flex aspect-square items-center justify-center text-sm text-muted">Photo coming soon</div>
                @endforelse
            </div>
            @if($product->images->count() > 1)
                <div class="mt-3 grid grid-cols-4 gap-3">
                    @foreach($product->images as $i => $img)
                        <button type="button" @click="active = {{ $i }}" class="border border-line hover:border-gold" :class="active === {{ $i }} ? 'border-gold' : ''" aria-label="Show image {{ $i + 1 }}">
                            <img src="{{ $img->url }}" alt="" class="aspect-square w-full object-cover" loading="lazy">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Details --}}
        <div>
            <p class="text-xs uppercase tracking-[0.25em] text-gold">{{ $product->fragrance_family ?? 'Fragrance' }} @if($product->category) &middot; {{ $product->category->name }}@endif</p>
            <h1 class="mt-3 font-display text-3xl leading-tight sm:text-4xl">{{ $product->name }}</h1>
            @if($average)
                <p class="mt-3 text-sm text-muted"><span class="text-gold">{{ str_repeat('★', (int) round($average)) }}</span> {{ $average }} / 5 &middot; {{ $reviews->count() }} {{ Str::plural('review', $reviews->count()) }}</p>
            @endif

            @if($variants->isEmpty())
                <div class="alert-info mt-6">Pricing for this fragrance is being confirmed. Please check back soon.</div>
            @else
                <div class="mt-6 flex items-baseline gap-3">
                    <p class="text-3xl text-gold" x-text="current ? formatINR(current.price) : ''"></p>
                    <template x-if="current && current.sale">
                        <s class="text-sm text-muted" x-text="formatINR(current.list)"></s>
                    </template>
                </div>

                <form method="post" action="{{ route('cart.items.store') }}" class="mt-8 space-y-6">
                    @csrf
                    <fieldset>
                        <legend class="label">Size</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach($variants as $v)
                                <label class="cursor-pointer">
                                    <input type="radio" name="variant_id" value="{{ $v->id }}" class="peer sr-only" x-model.number="selected" @checked($loop->first)>
                                    <span class="inline-block rounded-sm border border-line px-4 py-2 text-sm transition peer-checked:border-gold peer-checked:text-gold peer-focus-visible:ring-2 peer-focus-visible:ring-gold hover:border-gold">
                                        {{ $v->name }} &middot; {{ money($v->effective_price) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="flex flex-wrap items-center gap-4 text-sm">
                        <span class="text-muted">SKU:</span> <span x-text="current?.sku"></span>
                        <span class="text-muted">Availability:</span>
                        <span :class="current && current.stock > 0 ? 'text-ok' : 'text-danger'" x-text="current?.label"></span>
                    </div>

                    <div class="flex flex-wrap items-end gap-4">
                        <div>
                            <label for="qty" class="label">Quantity</label>
                            <div class="flex items-center border border-line">
                                <button type="button" class="px-4 py-3 hover:text-gold" @click="qty = Math.max(1, qty - 1)" aria-label="Decrease quantity">&minus;</button>
                                <input id="qty" name="quantity" type="number" min="1" :max="maxQty" x-model.number="qty" class="w-14 border-x border-line bg-transparent py-3 text-center focus:outline-none">
                                <button type="button" class="px-4 py-3 hover:text-gold" @click="qty = Math.min(maxQty, qty + 1)" aria-label="Increase quantity">+</button>
                            </div>
                        </div>
                        <button class="btn-gold flex-1" :disabled="!current || current.stock <= 0">Add to Cart</button>
                    </div>
                </form>

                <div class="mt-4 flex flex-wrap gap-3">
                    <form method="post" action="{{ route('cart.buy-now') }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="variant_id" :value="selected">
                        <input type="hidden" name="quantity" :value="qty">
                        <button class="btn-outline w-full" :disabled="!current || current.stock <= 0">Buy Now</button>
                    </form>
                    @auth
                        <form method="post" action="{{ route('wishlist.toggle', $product) }}">@csrf<button class="btn-ghost">Wishlist</button></form>
                    @endauth
                </div>
            @endif

            @if($product->short_description)<p class="mt-8 leading-relaxed text-muted">{{ $product->short_description }}</p>@endif

            @if($product->top_notes || $product->heart_notes || $product->base_notes)
                <dl class="mt-8 grid gap-4 border-t border-line pt-6 text-sm sm:grid-cols-3">
                    @foreach(['Top notes' => $product->top_notes, 'Heart notes' => $product->heart_notes, 'Base notes' => $product->base_notes] as $label => $value)
                        @if($value)<div><dt class="label">{{ $label }}</dt><dd>{{ $value }}</dd></div>@endif
                    @endforeach
                </dl>
            @endif

            @if($product->longevity || $product->projection)
                <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                    @if($product->longevity)<div><dt class="label">Longevity</dt><dd>{{ $product->longevity }}</dd></div>@endif
                    @if($product->projection)<div><dt class="label">Projection</dt><dd>{{ $product->projection }}</dd></div>@endif
                </dl>
            @endif

            @if(is_array($product->specifications) && count($product->specifications))
                <dl class="mt-6 divide-y divide-line border-y border-line text-sm">
                    @foreach($product->specifications as $spec)
                        <div class="grid grid-cols-3 gap-4 py-3"><dt class="text-muted">{{ $spec['label'] }}</dt><dd class="col-span-2">{{ $spec['value'] }}</dd></div>
                    @endforeach
                </dl>
            @endif

            <details class="mt-6 border-b border-line pb-4 text-sm">
                <summary class="cursor-pointer font-medium uppercase tracking-widest">Shipping &amp; Returns</summary>
                <p class="mt-3 text-muted">Free shipping on qualifying orders. <a class="text-gold hover:underline" href="{{ route('pages.shipping') }}">Shipping policy</a> &middot; <a class="text-gold hover:underline" href="{{ route('pages.returns') }}">Returns &amp; refunds</a></p>
            </details>

            <div class="mt-6 flex items-center gap-4 text-xs text-muted">
                <span>Share:</span>
                <a class="hover:text-gold" target="_blank" rel="noopener" href="https://wa.me/?text={{ urlencode($product->name.' '.url()->current()) }}">WhatsApp</a>
                <a class="hover:text-gold" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($product->name) }}">X</a>
                <button type="button" class="hover:text-gold" onclick="navigator.clipboard?.writeText(location.href)">Copy link</button>
            </div>
        </div>
    </div>

    {{-- Reviews --}}
    <section class="mt-24" aria-labelledby="rev">
        <h2 id="rev" class="section-title">Customer Reviews</h2>
        @if($reviews->isEmpty())
            <p class="mt-4 text-sm text-muted">No reviews yet. Reviews appear here after a delivered order is reviewed and approved.</p>
        @else
            <ul class="mt-8 grid gap-5 md:grid-cols-2">
                @foreach($reviews as $r)
                    <li class="card p-6">
                        <p class="text-gold">{{ str_repeat('★', $r->rating) }}</p>
                        @if($r->title)<p class="mt-2 font-medium">{{ $r->title }}</p>@endif
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $r->body }}</p>
                        <p class="mt-3 text-xs text-muted">{{ $r->user->name }} &middot; {{ $r->created_at->format('d M Y') }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
        @auth
            <details class="card mt-8 max-w-xl p-6">
                <summary class="cursor-pointer font-medium">Write a review</summary>
                <form method="post" action="{{ route('reviews.store', $product) }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="r-rating" class="label">Rating</label>
                        <select id="r-rating" name="rating" class="input" required>
                            @for($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>@endfor
                        </select>
                    </div>
                    <div><label for="r-title" class="label">Title (optional)</label><input id="r-title" name="title" class="input" maxlength="120"></div>
                    <div><label for="r-body" class="label">Your review</label><textarea id="r-body" name="body" rows="4" class="input" required minlength="10" maxlength="2000"></textarea></div>
                    <button class="btn-gold">Submit review</button>
                    <p class="text-xs text-muted">Only customers with a delivered order can review. Reviews are moderated before they appear.</p>
                </form>
            </details>
        @endauth
    </section>

    {{-- Related --}}
    @if($related->isNotEmpty())
        <section class="mt-24" aria-labelledby="rel">
            <h2 id="rel" class="section-title">You May Also Like</h2>
            <div class="mt-8 grid grid-cols-2 gap-5 lg:grid-cols-4">
                @foreach($related as $p)<x-product-card :product="$p" />@endforeach
            </div>
        </section>
    @endif
</div>

<script>
    function formatINR(v) {
        if (v === null || v === undefined || v === '') return '';
        const n = Math.round(parseFloat(v) * 100) / 100;
        const [int, dec] = n.toFixed(2).split('.');
        let last3 = int.slice(-3), rest = int.slice(0, -3);
        if (rest) rest = rest.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',';
        return '₹' + rest + last3 + (dec === '00' ? '' : '.' + dec);
    }
</script>
@endsection
