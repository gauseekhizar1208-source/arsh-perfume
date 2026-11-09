<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Http\Requests\CheckoutRequest;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\RazorpayService;
use App\Support\OrderAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cart,
        protected OrderService $orders,
        protected RazorpayService $razorpay,
    ) {
    }

    public function show(Request $request): View|RedirectResponse
    {
        $summary = $this->cart->summary($this->cart->current(), $request->user()?->email);

        if ($summary['is_empty']) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }
        if (! $request->user() && ! setting('guest_checkout_enabled', true)) {
            return redirect()->route('login')->with('error', 'Please sign in to place your order.');
        }

        return view('pages.checkout', [
            'summary' => $summary,
            'addresses' => $request->user()?->addresses ?? collect(),
            'states' => config('arsh.states'),
            'idempotencyKey' => Str::uuid()->toString(),
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        if (! $request->user() && ! setting('guest_checkout_enabled', true)) {
            return redirect()->route('login');
        }

        $cart = $this->cart->current();
        if (! $cart) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        try {
            $order = $this->orders->place($request->user(), $cart, $request->validated(), $request->ip());
        } catch (CheckoutException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        OrderAccess::grant($order);

        // No online payment: send the customer straight to WhatsApp with the full
        // order (items + quantities + address). Payment is handled in the chat.
        return redirect()->away($order->loadMissing('items')->whatsappOrderUrl());
    }
}
