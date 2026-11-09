<?php

namespace App\Services;

use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Pure totals calculation shared by the cart page, checkout page and order creation, so a customer is
 * always shown exactly what they will be charged. All maths is in integer paise.
 */
class PricingService
{
    /**
     * @param  Collection<int, array{unit_price_paise: int, quantity: int}>  $lines
     * @param  int  $discountPaise  already validated by CouponService
     * @return array{
     *   subtotal_paise:int, discount_paise:int, shipping_paise:int, tax_paise:int, total_paise:int,
     *   subtotal:string, discount:string, shipping:string, tax:string, total:string,
     *   prices_include_tax:bool, tax_enabled:bool, tax_rate:string, free_shipping_remaining:?string
     * }
     */
    public function calculate(Collection $lines, int $discountPaise = 0): array
    {
        $subtotal = (int) $lines->sum(fn ($l) => $l['unit_price_paise'] * $l['quantity']);
        $discount = max(0, min($discountPaise, $subtotal));
        $taxable = $subtotal - $discount;

        $shipping = 0;
        $remaining = null;
        if ($lines->isNotEmpty()) {
            $flat = Money::toPaise(setting('shipping_flat_rate', '0'));
            $threshold = Money::toPaise(setting('free_shipping_threshold', '0'));

            if ($threshold > 0 && $taxable >= $threshold) {
                $shipping = 0;
            } else {
                $shipping = $flat;
                if ($threshold > 0 && $flat > 0) {
                    $remaining = Money::fromPaise($threshold - $taxable);
                }
            }
        }

        $taxEnabled = (bool) setting('tax_enabled', false);
        $includesTax = (bool) setting('prices_include_tax', true);
        $rate = (float) setting('tax_rate', '0');
        $tax = 0;

        if ($taxEnabled && $rate > 0 && $taxable > 0) {
            if ($includesTax) {
                // Informational: the tax portion already contained in the (discounted) price.
                $tax = (int) round($taxable - ($taxable / (1 + $rate / 100)));
                $total = $taxable + $shipping;
            } else {
                $tax = (int) round($taxable * $rate / 100);
                $total = $taxable + $shipping + $tax;
            }
        } else {
            $total = $taxable + $shipping;
        }

        return [
            'subtotal_paise' => $subtotal,
            'discount_paise' => $discount,
            'shipping_paise' => $shipping,
            'tax_paise' => $tax,
            'total_paise' => $total,
            'subtotal' => Money::fromPaise($subtotal),
            'discount' => Money::fromPaise($discount),
            'shipping' => Money::fromPaise($shipping),
            'tax' => Money::fromPaise($tax),
            'total' => Money::fromPaise($total),
            'prices_include_tax' => $includesTax,
            'tax_enabled' => $taxEnabled && $rate > 0,
            'tax_rate' => (string) $rate,
            'free_shipping_remaining' => $remaining,
        ];
    }
}
