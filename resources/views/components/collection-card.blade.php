@props(['category', 'number' => 1])
<a class="collection-tile" href="{{ route('collections.show', $category) }}">
    <span class="collection-number">THE COLLECTION / {{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span>
    <div class="collection-sculpture" aria-hidden="true"><span class="scent-orb"></span><span class="scent-plinth"></span><span class="scent-ring"></span></div>
    <h3>{{ $category->name }}</h3>
    <div class="collection-meta"><span>{{ $category->products_count }} {{ Str::plural('fragrance', $category->products_count) }}</span><span class="collection-arrow" aria-hidden="true">&nearr;</span></div>
</a>
