<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $wishlist = Wishlist::query()->firstOrCreate(['user_id' => $request->user()->id]);

        return view('account.wishlist', [
            'products' => $wishlist->products()->with(['images', 'variants'])->get(),
        ]);
    }

    /** Toggle: add when absent, remove when present. */
    public function toggle(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === Product::STATUS_ACTIVE, 404);

        $wishlist = Wishlist::query()->firstOrCreate(['user_id' => $request->user()->id]);
        $wishlist->products()->toggle([$product->id]);

        return back()->with('status', 'Wishlist updated.');
    }
}
