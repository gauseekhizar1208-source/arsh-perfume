<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        // Only active products with a purchasable variant are public. Drafts and archived items are 404.
        abort_unless($product->status === Product::STATUS_ACTIVE, 404);

        $product->load([
            'images', 'category',
            'variants' => fn ($q) => $q->orderBy('sort_order'),
            'approvedReviews.user:id,name',
        ]);

        $related = Product::query()->visibleInShop()
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->with(['images', 'variants'])
            ->limit(4)->get();

        return view('pages.product', [
            'product' => $product,
            'variants' => $product->variants->filter->is_purchasable->values(),
            'related' => $related,
            'reviews' => $product->approvedReviews,
            'average' => $product->average_rating,
        ]);
    }
}
