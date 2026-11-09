<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    private const SORTS = ['newest', 'price_asc', 'price_desc', 'popular'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'family' => ['nullable', 'string', 'max:60'],
            'min' => ['nullable', 'numeric', 'min:0'],
            'max' => ['nullable', 'numeric', 'min:0'],
            'availability' => ['nullable', 'in:in_stock'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTS)],
        ]);

        $query = Product::query()->visibleInShop()->with(['images', 'variants'])
            ->search($filters['q'] ?? null);

        if ($slug = $filters['category'] ?? null) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
        }
        if ($family = $filters['family'] ?? null) {
            $query->where('fragrance_family', $family);
        }
        if ($filters['availability'] ?? null) {
            $query->whereHas('variants', fn ($q) => $q->purchasable()->where('stock', '>', 0));
        }
        if (isset($filters['min']) || isset($filters['max'])) {
            $query->whereHas('variants', function ($q) use ($filters) {
                $q->purchasable();
                if (isset($filters['min'])) {
                    $q->where('price', '>=', $filters['min']);
                }
                if (isset($filters['max'])) {
                    $q->where('price', '<=', $filters['max']);
                }
            });
        }

        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderByRaw('(SELECT MIN(COALESCE(sale_price, price)) FROM product_variants pv WHERE pv.product_id = products.id AND pv.is_active = 1 AND pv.price > 0) ASC'),
            'price_desc' => $query->orderByRaw('(SELECT MIN(COALESCE(sale_price, price)) FROM product_variants pv WHERE pv.product_id = products.id AND pv.is_active = 1 AND pv.price > 0) DESC'),
            'popular' => $query->orderByDesc('sales_count'),
            default => $query->latest('created_at'),
        };

        return view('pages.shop', [
            'products' => $query->paginate(12)->withQueryString(),
            'categories' => Category::query()->active()->ordered()->get(),
            'families' => config('arsh.fragrance_families'),
            'filters' => $filters,
            'sorts' => self::SORTS,
        ]);
    }
}
