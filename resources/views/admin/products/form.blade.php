@extends('layouts.admin')

@php($isNew = ! $product->exists)
@section('title', $isNew ? 'New product' : 'Edit '.$product->name)
@section('heading', $isNew ? 'New product' : $product->name)

@section('content')
<form method="post" enctype="multipart/form-data"
      action="{{ $isNew ? route('admin.products.store') : route('admin.products.update', $product) }}"
      class="grid gap-8 xl:grid-cols-3"
      x-data="{ variants: {{ $variants->count() }} }">
    @csrf
    @unless($isNew) @method('PUT') @endunless

    <div class="space-y-8 xl:col-span-2">
        <section class="card space-y-5 p-6" aria-labelledby="basics">
            <h2 id="basics" class="font-display text-lg">Basics</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="label" for="name">Name</label><input id="name" name="name" class="input" value="{{ old('name', $product->name) }}" required maxlength="150"></div>
                <div><label class="label" for="sku">Product SKU</label><input id="sku" name="sku" class="input" value="{{ old('sku', $product->sku) }}" required maxlength="64"></div>
                <div><label class="label" for="slug">URL slug</label><input id="slug" name="slug" class="input" value="{{ old('slug', $product->slug) }}" placeholder="auto from name" maxlength="160"></div>
                <div><label class="label" for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="input"><option value="">None</option>
                        @foreach($categories as $c)<option value="{{ $c->id }}" @selected((string) old('category_id', $product->category_id) === (string) $c->id)>{{ $c->name }}</option>@endforeach
                    </select></div>
                <div><label class="label" for="fragrance_family">Fragrance family</label>
                    <select id="fragrance_family" name="fragrance_family" class="input"><option value="">Not set</option>
                        @foreach(config('arsh.fragrance_families') as $f)<option value="{{ $f }}" @selected(old('fragrance_family', $product->fragrance_family) === $f)>{{ $f }}</option>@endforeach
                    </select></div>
                <div class="sm:col-span-2"><label class="label" for="short_description">Short description</label><textarea id="short_description" name="short_description" rows="2" class="input" maxlength="300">{{ old('short_description', $product->short_description) }}</textarea></div>
                <div class="sm:col-span-2"><label class="label" for="description">Full description</label><textarea id="description" name="description" rows="8" class="input" maxlength="10000">{{ old('description', $product->description) }}</textarea></div>
            </div>
        </section>

        <section class="card space-y-5 p-6" aria-labelledby="variants">
            <div class="flex items-center justify-between">
                <h2 id="variants" class="font-display text-lg">Sizes, prices &amp; stock</h2>
                <button type="button" class="btn-ghost btn-sm" @click="variants = Math.min(10, variants + 1)">Add size</button>
            </div>
            <p class="text-xs text-muted">A blank price keeps that size hidden from shoppers. Sale price must be lower than the regular price. Stock changes are logged.</p>
            @foreach($variants as $i => $v)
                <fieldset class="grid gap-4 border border-line p-4 sm:grid-cols-6" x-show="{{ $i }} < variants" x-cloak>
                    <legend class="px-2 text-xs text-gold">Size {{ $i + 1 }}</legend>
                    <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $v->id }}">
                    <div class="sm:col-span-2"><label class="label" for="v-name-{{ $i }}">Label</label><input id="v-name-{{ $i }}" name="variants[{{ $i }}][name]" class="input" value="{{ old("variants.$i.name", $v->name ?? '8 ml') }}" required></div>
                    <div><label class="label" for="v-sku-{{ $i }}">SKU</label><input id="v-sku-{{ $i }}" name="variants[{{ $i }}][sku]" class="input" value="{{ old("variants.$i.sku", $v->sku ?? $product->sku.'-'.($i+1)) }}" required></div>
                    <div><label class="label" for="v-ml-{{ $i }}">Size (ml)</label><input id="v-ml-{{ $i }}" name="variants[{{ $i }}][size_ml]" type="number" min="1" class="input" value="{{ old("variants.$i.size_ml", $v->size_ml) }}"></div>
                    <div><label class="label" for="v-price-{{ $i }}">Price (₹)</label><input id="v-price-{{ $i }}" name="variants[{{ $i }}][price]" type="number" step="0.01" min="0" class="input" value="{{ old("variants.$i.price", $v->price) }}"></div>
                    <div><label class="label" for="v-sale-{{ $i }}">Sale price (₹)</label><input id="v-sale-{{ $i }}" name="variants[{{ $i }}][sale_price]" type="number" step="0.01" min="0" class="input" value="{{ old("variants.$i.sale_price", $v->sale_price) }}"></div>
                    <div><label class="label" for="v-stock-{{ $i }}">Stock</label><input id="v-stock-{{ $i }}" name="variants[{{ $i }}][stock]" type="number" min="0" class="input" value="{{ old("variants.$i.stock", $v->stock) }}" required></div>
                    <div><label class="label" for="v-low-{{ $i }}">Low-stock alert at</label><input id="v-low-{{ $i }}" name="variants[{{ $i }}][low_stock_threshold]" type="number" min="0" class="input" value="{{ old("variants.$i.low_stock_threshold", $v->low_stock_threshold) }}"></div>
                    <input type="hidden" name="variants[{{ $i }}][is_active]" value="0"><label class="flex items-center gap-2 self-end text-sm sm:col-span-2"><input type="checkbox" name="variants[{{ $i }}][is_active]" value="1" class="accent-[#C8A24D]" @checked($v->is_active ?? true)> Active (shown to shoppers)</label>
                </fieldset>
            @endforeach
            @for($j = $variants->count(); $j < 10; $j++)
                <fieldset class="grid gap-4 border border-dashed border-line p-4 sm:grid-cols-6" x-show="{{ $j }} < variants" :disabled="{{ $j }} >= variants" x-cloak>
                    <legend class="px-2 text-xs text-gold">Size {{ $j + 1 }}</legend>
                    <div class="sm:col-span-2"><label class="label" for="n-name-{{ $j }}">Label</label><input id="n-name-{{ $j }}" name="variants[{{ $j }}][name]" class="input" placeholder="e.g. 8 ml"></div>
                    <div><label class="label" for="n-sku-{{ $j }}">SKU</label><input id="n-sku-{{ $j }}" name="variants[{{ $j }}][sku]" class="input"></div>
                    <div><label class="label" for="n-ml-{{ $j }}">Size (ml)</label><input id="n-ml-{{ $j }}" name="variants[{{ $j }}][size_ml]" type="number" min="1" class="input"></div>
                    <div><label class="label" for="n-price-{{ $j }}">Price (₹)</label><input id="n-price-{{ $j }}" name="variants[{{ $j }}][price]" type="number" step="0.01" min="0" class="input"></div>
                    <div><label class="label" for="n-sale-{{ $j }}">Sale price (₹)</label><input id="n-sale-{{ $j }}" name="variants[{{ $j }}][sale_price]" type="number" step="0.01" min="0" class="input"></div>
                    <div><label class="label" for="n-stock-{{ $j }}">Stock</label><input id="n-stock-{{ $j }}" name="variants[{{ $j }}][stock]" type="number" min="0" class="input" value="0"></div>
                    <input type="hidden" name="variants[{{ $j }}][is_active]" value="0"><label class="flex items-center gap-2 self-end text-sm sm:col-span-2"><input type="checkbox" name="variants[{{ $j }}][is_active]" value="1" class="accent-[#C8A24D]" checked> Active (shown to shoppers)</label>
                </fieldset>
            @endfor
        </section>

        <section class="card space-y-5 p-6" aria-labelledby="notes">
            <h2 id="notes" class="font-display text-lg">Fragrance notes &amp; performance</h2>
            <p class="text-xs text-muted">Only enter what you have confirmed. Empty fields are hidden from the storefront.</p>
            <div class="grid gap-5 sm:grid-cols-3">
                <div><label class="label" for="top_notes">Top notes</label><input id="top_notes" name="top_notes" class="input" value="{{ old('top_notes', $product->top_notes) }}"></div>
                <div><label class="label" for="heart_notes">Heart notes</label><input id="heart_notes" name="heart_notes" class="input" value="{{ old('heart_notes', $product->heart_notes) }}"></div>
                <div><label class="label" for="base_notes">Base notes</label><input id="base_notes" name="base_notes" class="input" value="{{ old('base_notes', $product->base_notes) }}"></div>
                <div><label class="label" for="longevity">Longevity</label><input id="longevity" name="longevity" class="input" value="{{ old('longevity', $product->longevity) }}" placeholder="e.g. 6–8 hours (as tested)"></div>
                <div><label class="label" for="projection">Projection</label><input id="projection" name="projection" class="input" value="{{ old('projection', $product->projection) }}"></div>
            </div>
        </section>

        <section class="card space-y-5 p-6" aria-labelledby="images">
            <h2 id="images" class="font-display text-lg">Images</h2>
            @if($product->images->isNotEmpty())
                <ul class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach($product->images as $img)
                        <li class="space-y-2 text-xs">
                            <img src="{{ $img->url }}" alt="" class="aspect-square w-full border border-line object-cover">
                            <div class="flex justify-between">
                                @if($img->is_primary)<span class="badge-gold">Primary</span>@else
                                    <form method="post" action="{{ route('admin.products.images.primary', [$product, $img]) }}">@csrf @method('PATCH')<button class="text-gold hover:underline">Make primary</button></form>
                                @endif
                                <form method="post" action="{{ route('admin.products.images.destroy', [$product, $img]) }}">@csrf @method('DELETE')<button class="text-muted hover:text-danger">Remove</button></form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
            <div>
                <label class="label" for="images-input">Upload images (JPG, PNG or WebP, up to 3 MB each, at least 300 px)</label>
                <input id="images-input" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="input file:mr-4 file:border-0 file:bg-gold file:px-4 file:py-2 file:text-ink">
                @error('images.*')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="card space-y-5 p-6" aria-labelledby="seo">
            <h2 id="seo" class="font-display text-lg">Specifications &amp; SEO</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label class="label" for="meta_title">SEO title (≤ 70)</label><input id="meta_title" name="meta_title" class="input" maxlength="70" value="{{ old('meta_title', $product->meta_title) }}"></div>
                <div><label class="label" for="meta_description">SEO description (≤ 160)</label><input id="meta_description" name="meta_description" class="input" maxlength="160" value="{{ old('meta_description', $product->meta_description) }}"></div>
            </div>
            @php($specs = old('specifications', $product->specifications ?? []))
            <div class="space-y-3">
                <p class="label">Specifications (e.g. Concentration, Ingredients)</p>
                @for($s = 0; $s < 5; $s++)
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input name="specifications[{{ $s }}][label]" class="input" placeholder="Label" aria-label="Specification {{ $s + 1 }} label" value="{{ $specs[$s]['label'] ?? '' }}">
                        <input name="specifications[{{ $s }}][value]" class="input" placeholder="Value" aria-label="Specification {{ $s + 1 }} value" value="{{ $specs[$s]['value'] ?? '' }}">
                    </div>
                @endfor
            </div>
        </section>
    </div>

    <aside class="space-y-8">
        <section class="card space-y-5 p-6" aria-labelledby="publish">
            <h2 id="publish" class="font-display text-lg">Publishing</h2>
            <div><label class="label" for="status">Status</label>
                <select id="status" name="status" class="input">
                    @foreach(['draft' => 'Draft (hidden)', 'active' => 'Active (on sale)', 'archived' => 'Archived'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('status', $product->status ?: 'draft') === $val)>{{ $label }}</option>
                    @endforeach
                </select></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" class="accent-[#C8A24D]" @checked($product->is_featured)> Featured on homepage</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_bestseller" value="1" class="accent-[#C8A24D]" @checked($product->is_bestseller)> Bestseller</label>
            <div><label class="label" for="owner_notes">Internal notes (not shown publicly)</label><textarea id="owner_notes" name="owner_notes" rows="3" class="input">{{ old('owner_notes', $product->owner_notes) }}</textarea></div>
            <button class="btn-gold w-full">{{ $isNew ? 'Create product' : 'Save changes' }}</button>
            @unless($isNew)
                <a href="{{ route('product.show', $product) }}" class="btn-ghost w-full" target="_blank" rel="noopener">View on storefront</a>
            @endunless
        </section>

        <section class="card p-6 text-sm" aria-labelledby="missing">
            <h2 id="missing" class="font-display text-base">Still missing</h2>
            @if(count($missing))
                <ul class="mt-3 list-disc space-y-1 pl-5 text-muted">@foreach($missing as $m)<li>{{ $m }}</li>@endforeach</ul>
            @else
                <p class="mt-3 text-ok">Ready to publish.</p>
            @endif
        </section>

        <div class="card p-6">
            <button type="button" class="btn-ghost w-full" @click="variants = Math.min(10, variants + 1)">Add another size</button>
        </div>
    </aside>
</form>
@endsection
