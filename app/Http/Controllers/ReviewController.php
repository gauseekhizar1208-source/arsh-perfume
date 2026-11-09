<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** Reviews go to moderation first; only approved reviews are shown publicly. */
    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === Product::STATUS_ACTIVE, 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        // Only customers with a delivered order for this product may review it.
        $orderId = Order::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'delivered')
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->value('id');

        abort_unless($orderId, 403, 'You can review a product after a delivered order.');
        abort_if(Review::query()->where('product_id', $product->id)->where('user_id', $request->user()->id)->exists(), 422, 'You have already reviewed this product.');

        (new Review)->forceFill([...$data, 'product_id' => $product->id, 'user_id' => $request->user()->id, 'order_id' => $orderId, 'status' => Review::PENDING])->save();

        return back()->with('status', 'Thank you! Your review will appear once approved.');
    }
}
