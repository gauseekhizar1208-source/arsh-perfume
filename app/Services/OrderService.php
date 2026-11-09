<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Exceptions\CheckoutException;
use App\Exceptions\CouponException;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\OrderStatusChangedNotification;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        protected PricingService $pricing,
        protected CouponService $coupons,
        protected InventoryService $inventory,
        protected RazorpayService $razorpay,
        protected UpiPaymentService $upi,
    ) {
    }

    /**
     * Turn a cart into an order. Everything happens in ONE transaction:
     *  - variant rows are locked (ordered by id to avoid deadlocks) and stock/price/availability re-verified,
     *  - stock is deducted (reserved) immediately and logged in inventory_movements,
     *  - the coupon row is locked and re-validated, usage recorded,
     *  - prices are snapshotted into order_items.
     * A duplicate form submission with the same idempotency key returns the original order instead of a new one.
     *
     * @param  array{name:string,email:string,phone:string,address_line1:string,address_line2?:?string,city:string,state:string,postal_code:string,payment_method:string,notes?:?string,idempotency_key?:?string}  $data
     *
     * @throws CheckoutException
     */
    public function place(?User $user, Cart $cart, array $data, ?string $ip = null): Order
    {
        $key = $data['idempotency_key'] ?? null;
        if ($key && ($existing = Order::query()->where('idempotency_key', $key)->first())) {
            return $existing;
        }

        $this->assertPaymentMethodAllowed($data['payment_method'] ?? '');

        try {
            $order = DB::transaction(fn () => $this->createOrder($user, $cart, $data, $ip));
        } catch (UniqueConstraintViolationException $e) {
            if ($key && ($existing = Order::query()->where('idempotency_key', $key)->first())) {
                return $existing; // lost a double-click race; the first request's order wins
            }
            throw $e;
        }

        event(new OrderPlaced($order));

        return $order;
    }

    protected function assertPaymentMethodAllowed(string $method): void
    {
        // Online payment gateways (COD, Razorpay, UPI) are retired — orders are placed over WhatsApp.
        if ($method !== 'whatsapp') {
            throw new CheckoutException('Orders are placed over WhatsApp.');
        }
    }

    protected function createOrder(?User $user, Cart $cart, array $data, ?string $ip): Order
    {
        $items = $cart->items()->get();
        if ($items->isEmpty()) {
            throw new CheckoutException('Your cart is empty.');
        }

        // 1. Lock variants in a stable order (prevents deadlocks, serialises competing buyers).
        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', $items->pluck('product_variant_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $lines = new Collection;
        foreach ($items as $item) {
            $variant = $variants->get($item->product_variant_id);
            $product = $variant?->product;

            if (! $variant || ! $product || $product->status !== Product::STATUS_ACTIVE || ! $variant->is_purchasable) {
                throw new CheckoutException(($product?->name ?? 'An item in your cart').' is no longer available. Please review your cart.');
            }
            if ($variant->stock < $item->quantity) {
                $label = $product->name.' ('.$variant->name.')';
                throw new CheckoutException($variant->stock <= 0
                    ? "$label is out of stock."
                    : "Only {$variant->stock} of $label left in stock.");
            }

            $lines->push([
                'variant' => $variant,
                'product' => $product,
                'quantity' => $item->quantity,
                'unit_price_paise' => Money::toPaise($variant->effective_price),
                'list_price_paise' => Money::toPaise($variant->price),
            ]);
        }

        // 2. Coupon: lock the row so usage limits cannot be exceeded by concurrent checkouts.
        $coupon = null;
        $discount = 0;
        $email = strtolower($data['email']);
        if ($cart->coupon_code) {
            $coupon = Coupon::query()->where('code', Coupon::normalise($cart->coupon_code))->lockForUpdate()->first();
            try {
                if (! $coupon) {
                    throw new CouponException('The coupon on your cart is no longer valid.');
                }
                $discount = $this->coupons->discountFor($coupon, $lines, $user?->id, $email);
            } catch (CouponException $e) {
                throw new CheckoutException($e->getMessage().' Remove the coupon and try again.');
            }
        }

        // 3. Totals computed on the server from current prices.
        $totals = $this->pricing->calculate($lines, $discount);

        // 4. Order header.
        $order = new Order;
        $order->forceFill([
            'order_number' => $this->generateOrderNumber(),
            'user_id' => $user?->id,
            'idempotency_key' => $data['idempotency_key'] ?? null,
            'status' => 'pending',
            'payment_method' => $data['payment_method'],
            'payment_status' => 'pending',
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'],
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'],
            'state' => $data['state'],
            'postal_code' => $data['postal_code'],
            'country' => 'India',
            'notes' => $data['notes'] ?? null,
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discount'],
            'shipping_total' => $totals['shipping'],
            'tax_total' => $totals['tax'],
            'grand_total' => $totals['total'],
            'coupon_id' => $coupon?->id,
            'coupon_code' => $coupon?->code,
            'placed_at' => now(),
            'ip_address' => $ip,
        ])->save();

        // 5. Items (historical snapshot) + stock reservation.
        foreach ($lines as $line) {
            /** @var ProductVariant $variant */
            $variant = $line['variant'];
            $product = $line['product'];

            $order->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'product_name' => $product->name,
                'variant_name' => $variant->name,
                'sku' => $variant->sku,
                'description_snapshot' => Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 500, ''),
                'image_path' => $product->images()->value('path'),
                'list_price' => Money::fromPaise($line['list_price_paise']),
                'unit_price' => Money::fromPaise($line['unit_price_paise']),
                'quantity' => $line['quantity'],
                'line_total' => Money::fromPaise($line['unit_price_paise'] * $line['quantity']),
            ]);

            $this->inventory->adjust($variant, -$line['quantity'], 'sale', "Order {$order->order_number}", $order);
            Product::query()->whereKey($product->id)->increment('sales_count', $line['quantity']);
        }

        // 6. Payment row + coupon usage + history.
        Payment::query()->create([
            'order_id' => $order->id,
            'provider' => $data['payment_method'],
            'amount' => $totals['total'],
            'currency' => 'INR',
            'status' => 'created',
            'meta' => $data['payment_method'] === 'upi' ? ['upi' => $this->upi->snapshot()] : null,
        ]);

        if ($coupon) {
            CouponUsage::query()->create([
                'coupon_id' => $coupon->id,
                'order_id' => $order->id,
                'user_id' => $user?->id,
                'email' => $email,
                'discount_amount' => $totals['discount'],
            ]);
            $coupon->increment('used_count');
        }

        $this->recordHistory($order, null, 'pending', 'Order placed ('.$order->payment_method_label.')', $user);

        // 7. Empty the cart (same transaction: either the order exists and the cart is empty, or neither).
        $cart->items()->delete();
        $cart->coupon_code = null;
        $cart->save();

        return $order;
    }

    protected function generateOrderNumber(): string
    {
        do {
            $number = 'AP'.now()->format('ymd').random_int(10000, 99999);
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }

    // ------------------------------------------------------------------
    // Status changes
    // ------------------------------------------------------------------

    /**
     * Move an order to a new status (validated against config('arsh.status_transitions')).
     * Cancelling restores stock and releases the coupon exactly once. Returning optionally restocks.
     *
     * @param  array{restock?: bool}  $options
     *
     * @throws CheckoutException
     */
    public function transition(Order $order, string $to, ?User $actor = null, ?string $note = null, array $options = []): Order
    {
        $fresh = DB::transaction(function () use ($order, $to, $actor, $note, $options) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! in_array($to, config("arsh.status_transitions.$from", []), true)) {
                throw new CheckoutException('An order that is '.order_status_label($from).' cannot be changed to '.order_status_label($to).'.');
            }

            // Never push an order forward until an online payment has really been received.
            if (in_array($locked->payment_method, ['razorpay', 'upi'], true) && $locked->payment_status !== 'paid' && $to !== 'cancelled') {
                throw new CheckoutException('The online payment for this order has not been received yet.');
            }

            if ($to === 'cancelled') {
                $this->applyCancellation($locked, $actor, $note);
            }

            if ($to === 'returned' && ! empty($options['restock'])) {
                $this->inventory->restoreForOrder($locked, 'return_restore', $actor);
            }

            if ($to === 'shipped') {
                $locked->shipped_at = now();
            }

            if ($to === 'delivered') {
                $locked->delivered_at = now();
                // Cash on Delivery: delivery is when the cash is collected.
                if ($locked->payment_method === 'cod' && $locked->payment_status === 'pending') {
                    $this->markPaymentPaid($locked);
                }
            }

            $locked->status = $to;
            $locked->save();

            $this->recordHistory($locked, $from, $to, $note, $actor);

            return $locked;
        });

        $this->notifyCustomer($fresh, $to);

        return $fresh;
    }

    /** Customer self-service cancellation (caller must have authorised it through OrderPolicy). */
    public function cancelByCustomer(Order $order, User $customer): Order
    {
        if (! $order->canBeCancelledByCustomer()) {
            throw new CheckoutException('This order can no longer be cancelled online. Please contact us.');
        }

        return $this->transition($order, 'cancelled', $customer, 'Cancelled by customer');
    }

    protected function applyCancellation(Order $order, ?User $actor, ?string $note): void
    {
        $this->inventory->restoreForOrder($order, 'cancellation_restore', $actor);

        // Release the coupon use so the customer (and the global limit) get it back.
        if ($order->coupon_id) {
            $usage = CouponUsage::query()->where('order_id', $order->id)->first();
            if ($usage) {
                $usage->delete();
                Coupon::query()->whereKey($order->coupon_id)->where('used_count', '>', 0)->decrement('used_count');
            }
        }

        if ($order->payment_status === 'pending' && in_array($order->payment_method, ['razorpay', 'upi'], true)) {
            $order->payment_status = 'failed';
            Payment::query()->where('order_id', $order->id)->where('status', 'created')->update(['status' => 'failed']);
        }

        $order->cancelled_at = now();
        $order->cancel_reason = $note ? Str::limit($note, 250, '') : null;
    }

    // ------------------------------------------------------------------
    // Payments & refunds (admin actions)
    // ------------------------------------------------------------------

    /** COD cash collected before delivery (e.g. paid at the courier hub). */
    public function markCodPaid(Order $order, User $actor): Order
    {
        return DB::transaction(function () use ($order, $actor) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_method !== 'cod' || $locked->payment_status !== 'pending' || $locked->status === 'cancelled') {
                throw new CheckoutException('Only pending Cash on Delivery orders can be marked as paid.');
            }

            $this->markPaymentPaid($locked);
            $locked->save();
            $this->recordHistory($locked, $locked->status, $locked->status, 'Cash on Delivery payment marked as collected', $actor);

            return $locked;
        });
    }

    /**
     * Record a full or partial refund. For paid online orders the refund is also sent to Razorpay (live mode).
     * Fully refunded orders become payment_status "refunded".
     *
     * @param  string|null  $amount  decimal string; null = everything still refundable
     *
     * @throws CheckoutException
     */
    public function refund(Order $order, ?string $amount, User $actor, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $amount, $actor, $note) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_status !== 'paid') {
                throw new CheckoutException('Only paid orders can be refunded.');
            }

            $remaining = Money::toPaise($locked->grand_total) - Money::toPaise($locked->refunded_amount);
            $paise = $amount === null || $amount === '' ? $remaining : Money::toPaise($amount);

            if ($paise <= 0 || $paise > $remaining) {
                throw new CheckoutException('The refund amount must be between '.Money::format('0.01').' and '.Money::format(Money::fromPaise($remaining)).'.');
            }

            $payment = Payment::query()->where('order_id', $locked->id)->where('status', 'paid')->latest('id')->first();

            if ($locked->payment_method === 'razorpay' && $payment?->provider_payment_id) {
                $result = $this->razorpay->refund($payment->provider_payment_id, $paise, $locked->order_number);
                $meta = $payment->meta ?? [];
                $meta['refund_ids'] = array_values(array_unique([...($meta['refund_ids'] ?? []), $result['id']]));
                $payment->meta = $meta;
            }

            $locked->refunded_amount = Money::fromPaise(Money::toPaise($locked->refunded_amount) + $paise);
            $full = Money::toPaise($locked->refunded_amount) >= Money::toPaise($locked->grand_total);
            if ($full) {
                $locked->payment_status = 'refunded';
                if ($payment) { $payment->status = 'refunded'; }
            }
            $locked->save();
            $payment?->save();

            $this->recordHistory(
                $locked, $locked->status, $locked->status,
                'Refund of '.Money::format(Money::fromPaise($paise)).' recorded'.($note ? ": $note" : ''),
                $actor
            );

            return $locked;
        });
    }

    /** Shared by COD collection and delivery. Caller holds the order lock and saves the order. */
    protected function markPaymentPaid(Order $order): void
    {
        $order->payment_status = 'paid';
        $order->paid_at = now();

        Payment::query()->where('order_id', $order->id)->whereIn('status', ['created', 'failed'])->update([
            'status' => 'paid',
            'verified_at' => now(),
        ]);
    }

    public function recordHistory(Order $order, ?string $from, string $to, ?string $note, ?User $actor): OrderStatusHistory
    {
        return OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note ? Str::limit($note, 250, '') : null,
            'changed_by' => $actor?->id,
        ]);
    }

    protected function notifyCustomer(Order $order, string $newStatus): void
    {
        if (! setting('notify_customer_status', true) || blank($order->email)) {
            return;
        }
        if (! in_array($newStatus, ['confirmed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled', 'returned'], true)) {
            return;
        }

        Notification::route('mail', $order->email)->notify(new OrderStatusChangedNotification($order));
    }
}
