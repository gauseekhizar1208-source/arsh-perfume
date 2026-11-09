<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Exceptions\CouponException;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(protected CartService $cart)
    {
    }

    public function index(Request $request): View
    {
        return view('pages.cart', [
            'summary' => $this->cart->summary($this->cart->current(), $request->user()?->email),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        try {
            $this->cart->add(ProductVariant::query()->findOrFail($data['variant_id']), $data['quantity'] ?? 1);
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Added to your cart.');
    }

    /** "Buy now": add the variant, then go straight to checkout. */
    public function buyNow(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        try {
            $this->cart->add(ProductVariant::query()->findOrFail($data['variant_id']), $data['quantity'] ?? 1);
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('checkout.show');
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        $this->ownsItem($item);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:10']]);

        try {
            $this->cart->updateQuantity($item, (int) $data['quantity']);
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Cart updated.');
    }

    public function destroy(CartItem $item): RedirectResponse
    {
        $this->ownsItem($item);
        $this->cart->remove($item);

        return back()->with('status', 'Item removed.');
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:50']]);

        try {
            $coupon = $this->cart->applyCoupon($this->cart->current(create: true), $data['code'], $request->user()?->email);
        } catch (CouponException $e) {
            return back()->with('coupon_error', $e->getMessage());
        }

        return back()->with('status', "Coupon {$coupon->code} applied.");
    }

    public function removeCoupon(): RedirectResponse
    {
        if ($cart = $this->cart->current()) {
            $this->cart->removeCoupon($cart);
        }

        return back()->with('status', 'Coupon removed.');
    }

    /** Cart items belong to the current customer or guest session only (IDOR protection). */
    protected function ownsItem(CartItem $item): void
    {
        $cart = $this->cart->current();
        abort_unless($cart && $item->cart_id === $cart->id, 404);
    }
}
