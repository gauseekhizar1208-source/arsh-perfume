<?php

namespace App\Support;

use App\Models\Order;
use App\Models\User;

/**
 * Who may see a specific order on the storefront?
 *  - the signed-in customer who owns it, or
 *  - the browser session that just placed it as a guest (granted at checkout).
 * Everyone else (including other customers) is refused. Guests can additionally look an order up on
 * /track-order with order number + email.
 */
final class OrderAccess
{
    public static function grant(Order $order): void
    {
        $ids = session('order_access', []);
        $ids[] = $order->id;
        session(['order_access' => array_values(array_unique($ids))]);
    }

    public static function allows(Order $order, ?User $user): bool
    {
        if ($user && $order->user_id !== null && $order->user_id === $user->id) {
            return true;
        }

        return in_array($order->id, (array) session('order_access', []), true);
    }
}
