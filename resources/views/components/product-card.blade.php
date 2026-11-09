@props(['product'])
@php
    $price = $product->starting_price;
    $list = $product->starting_list_price;
    $showList = $list && $price && (float) $list > (float) $price;
@endphp
<article class="product-card card-hover group flex flex-col overflow-hidden">
    <a href="{{ route('product.show', $product) }}" class="product-image relative block overflow-hidden bg-panel" aria-label="View {{ $product->name }}">
        <img src="{{ $product->primary_image_url }}" alt="{{ $product->images->first()?->alt ?: $product->name }}"
             loading="lazy" decoding="async" width="600" height="600"
             class="block h-auto w-full">
        @if($product->is_bestseller)<span class="badge-gold absolute left-3 top-3 bg-ink/80">Bestseller</span>@endif
        @if($product->is_on_sale ?? false)<span class="badge-ok absolute right-3 top-3 bg-ink/80">Sale</span>@endif
    </a>
    <div class="product-info flex flex-1 flex-col gap-2 p-4">
        <p class="text-[11px] uppercase tracking-widest text-muted">{{ $product->fragrance_family ?? 'Fragrance' }}</p>
        <h3 class="font-display text-base leading-snug"><a href="{{ route('product.show', $product) }}" class="hover:text-gold">{{ $product->name }}</a></h3>
        <p class="line-clamp-2 text-sm text-muted">{{ $product->short_description }}</p>
        <div class="mt-auto flex items-end justify-between gap-3 pt-3">
            <div>
                @if($price)
                    <p class="text-gold">
                        @if($showList)<s class="mr-1 text-xs text-muted">{{ money($list) }}</s>@endif
                        {{ $product->variants->count() > 1 ? 'from ' : '' }}{{ money($price) }}
                    </p>
                @else
                    <p class="text-sm text-muted">Price coming soon</p>
                @endif
                <p class="text-xs {{ $product->in_stock ? 'text-ok' : 'text-danger' }}">{{ $product->availability_label }}</p>
            </div>
            <a href="{{ route('product.show', $product) }}" class="btn-outline btn-sm">Explore &nearr;</a>
        </div>
    </div>
</article>
