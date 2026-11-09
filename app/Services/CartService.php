<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Exceptions\CouponException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Database-backed cart. Authenticated customers have one persistent cart per user; guests have a cart keyed by a
 * random token stored in their session (it survives session id regeneration at login and is merged on login).
 */
class CartService
{
    public const MAX_PER_LINE = 10;

    public function __construct(
        protected PricingService $pricing,
        protected CouponService $coupons,
    ) {
    }

    public function current(bool $create = false): ?Cart
    {
        if (Auth::check()) {
            $cart = Cart::query()->where('user_id', Auth::id())->first();
            if (! $cart && $create) {
                $cart = new Cart;
                $cart->user_id = Auth::id();
                $cart->save();
            }

            return $cart;
        }

        $token = session('cart_token');
        $cart = $token ? Cart::query()->where('session_token', $token)->whereNull('user_id')->first() : null;

        if (! $cart && $create) {
            $token = Str::random(40);
            session(['cart_token' => $token]);
            $cart = new Cart;
            $cart->session_token = $token;
            $cart->save();
        }

        return $cart;
    }

    /** Number of units in the cart (header badge). */
    public function count(): int
    {
        $cart = $this->current();

        return $cart ? (int) $cart->items()->sum('quantity') : 0;
    }

    /** @throws CheckoutException */
    public function add(ProductVariant $variant, int $quantity = 1): CartItem
    {
        $variant->loadMissing('product');
        $this->assertPurchasable($variant);

        $quantity = max(1, $quantity);
        $cart = $this->current(create: true);

        return DB::transaction(function () use ($cart, $variant, $quantity) {
            $item = $cart->items()->where('product_variant_id', $variant->id)->lockForUpdate()->first();
            $wanted = ($item?->quantity ?? 0) + $quantity;
            $wanted = $this->clampToStock($variant, $wanted);

            if ($item) {
                $item->quantity = $wanted;
                $item->save();
            } else {
                $item = $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => $wanted]);
            }

            return $item;
        });
    }

    /** @throws CheckoutException */
    public function updateQuantity(CartItem $item, int $quantity): ?CartItem
    {
        if ($quantity <= 0) {
            $item->delete();

            return null;
        }

        $variant = $item->variant()->with('product')->first();
        if (! $variant) {
            $item->delete();
            throw new CheckoutException('That item is no longer available and was removed from your cart.');
        }
        $this->assertPurchasable($variant);

        $item->quantity = $this->clampToStock($variant, $quantity);
        $item->save();

        return $item;
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->coupon_code = null;
        $cart->save();
    }

    /** @throws CouponException */
    public function applyCoupon(Cart $cart, string $code, ?string $email = null): Coupon
    {
        $coupon = $this->coupons->findByCode($code);
        if (! $coupon) {
            throw new CouponException('That coupon code is not valid.');
        }

        $lines = $this->summary($cart)['pricing_lines'];
        if ($lines->isEmpty()) {
            throw new CouponException('Your cart is empty.');
        }

        // Throws with the specific reason (expired, minimum order, limit reached...)
        $this->coupons->discountFor($coupon, $lines, Auth::id(), $email ?? Auth::user()?->email);

        $cart->coupon_code = $coupon->code;
        $cart->save();

        return $coupon;
    }

    public function removeCoupon(Cart $cart): void
    {
        $cart->coupon_code = null;
        $cart->save();
    }

    /**
     * Full, server-verified view of the cart: current prices, stock problems, coupon and shipping.
     *
     * @return array{
     *   lines: Collection, pricing_lines: Collection, issues: array<int,string>, totals: array,
     *   coupon: ?Coupon, coupon_error: ?string, count: int, is_empty: bool
     * }
     */
    public function summary(?Cart $cart, ?string $email = null): array
    {
        $empty = [
            'lines' => collect(), 'pricing_lines' => collect(), 'issues' => [],
            'totals' => $this->pricing->calculate(collect(), 0),
            'coupon' => null, 'coupon_error' => null, 'count' => 0, 'is_empty' => true,
        ];

        if (! $cart) {
            return $empty;
        }

        $items = $cart->items()->with(['variant.product.images'])->get();
        if ($items->isEmpty()) {
            return $empty;
        }

        $lines = collect();
        $pricingLines = collect();
        $issues = [];

        foreach ($items as $item) {
            $variant = $item->variant;
            $product = $variant?->product;

            $issue = null;
            if (! $variant || ! $product || $product->status !== 'active' || ! $variant->is_purchasable) {
                $issue = 'No longer available';
            } elseif ($variant->stock <= 0) {
                $issue = 'Out of stock';
            } elseif ($variant->stock < $item->quantity) {
                $issue = "Only {$variant->stock} left in stock";
            }

            $unitPaise = $variant ? Money::toPaise($variant->effective_price) : 0;
            $line = [
                'item' => $item,
                'variant' => $variant,
                'product' => $product,
                'name' => $product?->name ?? 'Unavailable product',
                'variant_name' => $variant?->name,
                'url' => $product ? route('product.show', $product->slug) : null,
                'image_url' => $product ? $product->primary_image_url : media_url(null),
                'quantity' => $item->quantity,
                'unit_price' => Money::fromPaise($unitPaise),
                'unit_price_paise' => $unitPaise,
                'list_price' => $variant?->price,
                'on_sale' => (bool) $variant?->is_on_sale,
                'line_total' => Money::fromPaise($unitPaise * $item->quantity),
                'line_total_paise' => $unitPaise * $item->quantity,
                'max_quantity' => $variant ? max(0, min($variant->stock, self::MAX_PER_LINE)) : 0,
                'issue' => $issue,
            ];

            $lines->push($line);
            if ($issue) {
                $issues[$item->id] = "{$line['name']}: $issue";
            } else {
                $pricingLines->push($line);
            }
        }

        // Coupon (re-validated on every view: it may have expired or the cart changed).
        $coupon = null;
        $couponError = null;
        $discount = 0;

        if ($cart->coupon_code && $pricingLines->isNotEmpty()) {
            $found = $this->coupons->findByCode($cart->coupon_code);
            try {
                if (! $found) {
                    throw new CouponException('That coupon is no longer valid.');
                }
                $discount = $this->coupons->discountFor($found, $pricingLines, Auth::id(), $email ?? Auth::user()?->email);
                $coupon = $found;
            } catch (CouponException $e) {
                $couponError = $e->getMessage();
                $cart->coupon_code = null;   // drop it so checkout is never blocked by a stale code
                $cart->save();
            }
        }

        return [
            'lines' => $lines,
            'pricing_lines' => $pricingLines,
            'issues' => $issues,
            'totals' => $this->pricing->calculate($pricingLines, $discount),
            'coupon' => $coupon,
            'coupon_error' => $couponError,
            'count' => (int) $items->sum('quantity'),
            'is_empty' => false,
        ];
    }

    /** After login: fold the guest cart (if any) into the customer's persistent cart. */
    public function mergeGuestCartInto(User $user): void
    {
        $token = session('cart_token');
        if (! $token) {
            return;
        }

        $guest = Cart::query()->where('session_token', $token)->whereNull('user_id')->first();
        session()->forget('cart_token');
        if (! $guest) {
            return;
        }

        DB::transaction(function () use ($guest, $user) {
            $cart = Cart::query()->where('user_id', $user->id)->first();
            if (! $cart) {
                $cart = new Cart;
                $cart->user_id = $user->id;
                $cart->save();
            }

            foreach ($guest->items()->with('variant.product')->get() as $item) {
                $variant = $item->variant;
                if (! $variant || ! $variant->product) {
                    continue;
                }
                $existing = $cart->items()->where('product_variant_id', $variant->id)->first();
                $qty = min(($existing?->quantity ?? 0) + $item->quantity, self::MAX_PER_LINE, max(1, $variant->stock));
                if ($existing) {
                    $existing->update(['quantity' => $qty]);
                } else {
                    $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => $qty]);
                }
            }

            if (! $cart->coupon_code && $guest->coupon_code) {
                $cart->coupon_code = $guest->coupon_code;
                $cart->save();
            }

            $guest->delete();
        });
    }

    protected function assertPurchasable(ProductVariant $variant): void
    {
        if (! $variant->product || $variant->product->status !== 'active' || ! $variant->is_purchasable) {
            throw new CheckoutException('Sorry, this product is not available right now.');
        }
        if ($variant->stock <= 0) {
            throw new CheckoutException('Sorry, this product is out of stock.');
        }
    }

    /** @throws CheckoutException when the wanted quantity exceeds stock or the per-line cap */
    protected function clampToStock(ProductVariant $variant, int $wanted): int
    {
        if ($wanted > $variant->stock) {
            throw new CheckoutException("Only {$variant->stock} available in stock.");
        }
        if ($wanted > self::MAX_PER_LINE) {
            throw new CheckoutException('You can order at most '.self::MAX_PER_LINE.' of this item at a time.');
        }

        return $wanted;
    }
}
