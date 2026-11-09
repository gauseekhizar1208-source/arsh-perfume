<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Storefront order access: a customer can only ever see or act on their own orders (IDOR protection).
 * Administrators use the separate /admin routes.
 */
class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $order->user_id !== null && $order->user_id === $user->id;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->view($user, $order) && $order->canBeCancelledByCustomer();
    }
}
