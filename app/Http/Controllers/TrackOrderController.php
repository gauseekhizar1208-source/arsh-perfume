<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackOrderController extends Controller
{
    /** Signed-in customers see their own orders; guests look up with order number + email. */
    public function index(Request $request): View
    {
        return view('pages.track-order', [
            'order' => null,
            'myOrders' => $request->user()?->orders()->latest('placed_at')->limit(10)->get() ?? collect(),
        ]);
    }

    public function lookup(Request $request): View
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        // Same generic message for "not found" and "email mismatch": no order enumeration.
        $order = Order::query()
            ->where('order_number', $data['order_number'])
            ->where('email', strtolower($data['email']))
            ->with(['statusHistories'])
            ->first();

        return view('pages.track-order', [
            'order' => $order,
            'notFound' => $order === null,
            'myOrders' => collect(),
        ]);
    }
}
