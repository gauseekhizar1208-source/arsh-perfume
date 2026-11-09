<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $listing = fn ($q) => $q->visibleInShop()->with(['images', 'variants'])->limit(8);

        return view('pages.home', [
            'heroBanners' => Banner::query()->where('position', 'hero')->live()->get(),
            'promoBanners' => Banner::query()->where('position', 'promo')->live()->get(),
            'categories' => Category::query()->active()->ordered()->withCount('products')->limit(9)->get(),
            'featured' => $listing(Product::query()->featured())->get(),
            'bestsellers' => $listing(Product::query()->bestsellers())->get(),
            'newArrivals' => $listing(Product::query()->latest('created_at'))->get(),
            'reviews' => Review::query()->approved()->with(['user:id,name', 'product:id,name,slug'])->latest()->limit(6)->get(),
        ]);
    }
}
