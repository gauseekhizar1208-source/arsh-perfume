<?php

namespace App\Services;

use App\Exceptions\CouponException;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Support\Money;
use Illuminate\Support\Collection;

class CouponService
{
    public function findByCode(string $code): ?Coupon
    {
        return Coupon::query()->where('code', Coupon::normalise($code))->first();
    }

    /**
     * Server-side coupon validation. Returns the discount in paise, or throws CouponException with the reason.
     *
     * @param  Collection<int, array{variant: \App\Models\ProductVariant, quantity: int, unit_price_paise: int}>  $lines
     */
    public function discountFor(Coupon $coupon, Collection $lines, ?int $userId = null, ?string $email = null): int
    {
        if (! $coupon->is_active) {
            throw new CouponException('This coupon is not active.');
        }
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            throw new CouponException('This coupon is not valid yet.');
        }
        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            throw new CouponException('This coupon has expired.');
        }
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw new CouponException('This coupon has reached its usage limit.');
        }

        if ($coupon->per_user_limit !== null && ($userId || $email)) {
            $used = CouponUsage::query()
                ->where('coupon_id', $coupon->id)
                ->where(function ($q) use ($userId, $email) {
                    if ($userId) {
                        $q->where('user_id', $userId);
                    }
                    if ($email) {
                        $userId ? $q->orWhere('email', strtolower($email)) : $q->where('email', strtolower($email));
                    }
                })
                ->count();

            if ($used >= $coupon->per_user_limit) {
                throw new CouponException('You have already used this coupon the maximum number of times.');
            }
        }

        $subtotal = (int) $lines->sum(fn ($l) => $l['unit_price_paise'] * $l['quantity']);

        if ($coupon->min_order_amount !== null && $subtotal < Money::toPaise($coupon->min_order_amount)) {
            throw new CouponException('A minimum order of '.Money::format($coupon->min_order_amount).' is needed for this coupon.');
        }

        $eligible = $this->eligibleSubtotal($coupon, $lines);
        if ($eligible <= 0) {
            throw new CouponException('This coupon does not apply to the items in your cart.');
        }

        $discount = $coupon->type === Coupon::TYPE_PERCENT
            ? (int) round($eligible * (float) $coupon->value / 100)
            : Money::toPaise($coupon->value);

        if ($coupon->max_discount !== null) {
            $discount = min($discount, Money::toPaise($coupon->max_discount));
        }

        $discount = min($discount, $eligible);

        if ($discount <= 0) {
            throw new CouponException('This coupon gives no discount on your cart.');
        }

        return $discount;
    }

    /** Subtotal (paise) of the lines this coupon may discount, honouring product/category restrictions. */
    protected function eligibleSubtotal(Coupon $coupon, Collection $lines): int
    {
        $productIds = $coupon->products()->pluck('products.id')->all();
        $categoryIds = $coupon->categories()->pluck('categories.id')->all();

        $restricted = $productIds !== [] || $categoryIds !== [];

        return (int) $lines->sum(function ($l) use ($restricted, $productIds, $categoryIds) {
            $product = $l['variant']->product;
            $eligible = ! $restricted
                || in_array($product->id, $productIds, true)
                || ($product->category_id && in_array($product->category_id, $categoryIds, true));

            return $eligible ? $l['unit_price_paise'] * $l['quantity'] : 0;
        });
    }
}
