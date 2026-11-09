<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(): View
    {
        return view('pages.collections', [
            'categories' => Category::query()->active()->ordered()->withCount('products')->get(),
        ]);
    }

    public function show(Category $category): View
    {
        abort_unless($category->is_active, 404);

        return view('pages.collection', [
            'category' => $category,
            'products' => Product::query()->visibleInShop()->where('category_id', $category->id)
                ->with(['images', 'variants'])->latest('created_at')->paginate(12),
        ]);
    }
}
