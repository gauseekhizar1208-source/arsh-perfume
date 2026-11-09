<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-_]+$/', Rule::unique('products', 'sku')->ignore($productId)],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9\-]+$/', Rule::unique('products', 'slug')->ignore($productId)],
            'short_description' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:10000'],
            'fragrance_family' => ['nullable', 'string', Rule::in(config('arsh.fragrance_families'))],
            'top_notes' => ['nullable', 'string', 'max:300'],
            'heart_notes' => ['nullable', 'string', 'max:300'],
            'base_notes' => ['nullable', 'string', 'max:300'],
            'longevity' => ['nullable', 'string', 'max:120'],
            'projection' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(Product::STATUSES)],
            'is_featured' => ['sometimes', 'boolean'],
            'is_bestseller' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'owner_notes' => ['nullable', 'string', 'max:1000'],
            'specifications' => ['nullable', 'array', 'max:20'],
            'specifications.*.label' => ['nullable', 'required_with:specifications.*.value', 'string', 'max:60'],
            'specifications.*.value' => ['nullable', 'required_with:specifications.*.label', 'string', 'max:200'],

            // Variants: one row per size. A blank price means "not confirmed yet" (not purchasable).
            'variants' => ['required', 'array', 'min:1', 'max:10'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['required', 'string', 'max:80'],
            'variants.*.size_ml' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'variants.*.sku' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-_]+$/', 'distinct'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'variants.*.is_active' => ['sometimes', 'boolean'],

            // Images: real raster images only, size-limited. Stored outside PHP execution paths.
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:min_width=300,min_height=300'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Derive the slug from the name when left blank, so the unique rule
        // catches duplicates instead of the database throwing a 500.
        $slug = $this->filled('slug')
            ? $this->input('slug')
            : \Illuminate\Support\Str::slug((string) $this->input('name'));
        if ($slug !== '' && $slug !== null) {
            $this->merge(['slug' => strtolower((string) $slug)]);
        }
        $variants = collect($this->input('variants', []))->map(function ($v) {
            $v['is_active'] = filter_var($v['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);

            return $v;
        })->all();
        $this->merge(['variants' => $variants]);
    }

    public function withValidator($validator): void
    {
        // A product may only become Active when it has the required fields (see Product::missingFields()).
        $validator->after(function ($validator) {
            foreach ((array) $this->input('variants', []) as $i => $v) {
                if (filled($v['sale_price'] ?? null) && filled($v['price'] ?? null) && (float) $v['sale_price'] >= (float) $v['price']) {
                    $validator->errors()->add("variants.$i.sale_price", 'The sale price must be lower than the regular price.');
                }
            }

            if ($this->input('status') !== Product::STATUS_ACTIVE) {
                return;
            }
            $hasPrice = collect($this->input('variants', []))
                ->contains(fn ($v) => ($v['is_active'] ?? false) && filled($v['price'] ?? null) && (float) $v['price'] > 0);
            if (! $hasPrice) {
                $validator->errors()->add('status', 'Set a price on at least one active size before publishing.');
            }
        });
    }
}