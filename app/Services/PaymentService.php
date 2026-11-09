<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Online payment lifecycle. The one rule: an order is NEVER marked paid unless
 *  1. the gateway order id belongs to THAT order,
 *  2. the HMAC signature (checkout callback) or webhook signature verifies,
 *  3. the paid amount equals the order total, and
 *  4. (live mode) the gateway itself reports the payment as captured/authorized.
 * Marking paid is idempotent, so the checkout callback and the webhook can both fire safely.
 */
class PaymentService
{
    public function __construct(
        protected RazorpayService $razorpay,
        protected OrderService $orders,
    ) {
    }

    /** Create the gateway order for an unpaid online order and remember its id on the payment row. */
    public function startOnlinePayment(Order $order): Payment
    {
        $payment = Payment::query()->where('order_id', $order->id)->where('provider', 'razorpay')->latest('id')->firstOrFail();

        if ($payment->provider_order_id && $payment->status === 'created') {
            return $payment; // reuse: page refresh must not create a second gateway order
        }

        $gateway = $this->razorpay->createGatewayOrder($order);
        $payment->provider_order_id = $gateway['id'];
        $payment->status = 'created';
        $payment->save();

        return $payment;
    }

    /**
     * Browser callback after the customer pays (Razorpay Checkout handler or the simulated gateway).
     *
     * @throws CheckoutException
     */
    public function verifyCheckoutCallback(Order $order, string $gatewayOrderId, string $paymentId, string $signature): Order
    {
        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('provider', 'razorpay')
            ->where('provider_order_id', $gatewayOrderId)
            ->first();

        // The gateway order must belong to this order, otherwise someone is replaying another order's payment.
        if (! $payment) {
            throw new CheckoutException('This payment does not belong to this order.');
        }

        if (! $this->razorpay->verifySignature($gatewayOrderId, $paymentId, $signature)) {
            throw new CheckoutException('Payment verification failed. If money was deducted, it will be refunded automatically.');
        }

        $amountPaise = Money::toPaise($order->grand_total);
        $method = null;

        if ($this->razorpay->isLive()) {
            $remote = $this->razorpay->fetchPayment($paymentId);

            if (($remote['order_id'] ?? null) !== $gatewayOrderId || (int) ($remote['amount'] ?? 0) !== $amountPaise) {
                throw new CheckoutException('The payment details do not match this order. Please contact support.');
            }
            if (($remote['status'] ?? '') === 'authorized') {
                $remote = $this->razorpay->capture($paymentId, $amountPaise);
            }
            if (($remote['status'] ?? '') !== 'captured') {
                throw new CheckoutException('Your payment has not been completed yet.');
            }
            $method = $remote['method'] ?? null;
        }

        return $this->recordSuccessfulPayment($order, $gatewayOrderId, $paymentId, $amountPaise, $method, 'checkout');
    }

    /**
     * Idempotently mark an order as paid.
     * Safe to call repeatedly (callback + webhook, retries).
     *
     * @throws CheckoutException when the amount does not match
     */
    public function recordSuccessfulPayment(
        Order $order,
        string $gatewayOrderId,
        string $paymentId,
        int $amountPaise,
        ?string $method,
        string $source,
    ): Order {
        return DB::transaction(function () use ($order, $gatewayOrderId, $paymentId, $amountPaise, $method, $source) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($amountPaise !== Money::toPaise($locked->grand_total)) {
                throw new CheckoutException('Paid amount does not match the order total.');
            }

            $payment = Payment::query()
                ->where('order_id', $locked->id)
                ->where('provider', 'razorpay')
                ->where('provider_order_id', $gatewayOrderId)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw new CheckoutException('Payment record not found for this order.');
            }

            // Already processed (second delivery of the same success): do nothing.
            if ($locked->payment_status === 'paid' || $payment->status === 'paid') {
                return $locked;
            }

            // A payment id can only ever settle one order.
            if (Payment::query()->where('provider_payment_id', $paymentId)->where('id', '!=', $payment->id)->exists()) {
                throw new CheckoutException('This payment has already been used for another order.');
            }

            $payment->forceFill([
                'provider_payment_id' => $paymentId,
                'status' => 'paid',
                'method' => $method,
                'verified_at' => now(),
                'error_code' => null,
                'error_description' => null,
            ])->save();

            $locked->payment_status = 'paid';
            $locked->paid_at = now();
            $locked->save();

            if ($locked->status === 'cancelled') {
                // Customer paid after the order was cancelled/expired: flag it, money must be refunded.
                $this->orders->recordHistory($locked, 'cancelled', 'cancelled', 'Payment received AFTER cancellation. Refund required.', null);

                return $locked;
            }

            $this->orders->recordHistory($locked, $locked->status, $locked->status, "Online payment received ($source)", null);

            if ($locked->status === 'pending') {
                $locked->status = 'confirmed';
                $locked->save();
                $this->orders->recordHistory($locked, 'pending', 'confirmed', 'Auto-confirmed after successful payment', null);
            }

            return $locked;
        });
    }

    /** The customer abandoned / failed the payment in the checkout UI. */
    public function recordFailedAttempt(Order $order, ?string $code = null, ?string $description = null): void
    {
        if ($order->payment_status === 'paid') {
            return;
        }

        Payment::query()->where('order_id', $order->id)->where('provider', 'razorpay')->where('status', 'created')
            ->update(['error_code' => $code, 'error_description' => $description ? mb_substr($description, 0, 250) : null]);
    }

    // ------------------------------------------------------------------
    // Webhooks
    // ------------------------------------------------------------------

    /**
     * Process a verified Razorpay webhook exactly once per event id.
     * Returns a short outcome string for logging; throws on processing errors so the controller can answer 5xx
     * and Razorpay retries later (the idempotency row is removed in that case).
     */
    public function handleWebhook(string $eventId, array $payload): string
    {
        try {
            $event = PaymentWebhookEvent::query()->create([
                'provider' => 'razorpay',
                'event_id' => $eventId,
                'event_type' => (string) ($payload['event'] ?? 'unknown'),
                'payload' => $payload,
            ]);
        } catch (UniqueConstraintViolationException) {
            return 'duplicate';
        }

        try {
            $outcome = $this->processWebhookPayload($payload);
        } catch (CheckoutException $e) {
            // Business mismatch (e.g. wrong amount): retrying will not help; record and acknowledge.
            $event->forceFill(['processed_at' => now(), 'outcome' => 'rejected: '.$e->getMessage()])->save();

            return 'rejected';
        } catch (Throwable $e) {
            $event->delete(); // allow Razorpay's retry to be processed
            throw $e;
        }

        $event->forceFill(['processed_at' => now(), 'outcome' => $outcome])->save();

        return $outcome;
    }

    protected function processWebhookPayload(array $payload): string
    {
        $type = (string) ($payload['event'] ?? '');
        $entity = $payload['payload']['payment']['entity'] ?? [];
        $gatewayOrderId = $entity['order_id'] ?? ($payload['payload']['order']['entity']['id'] ?? null);

        if (in_array($type, ['payment.captured', 'order.paid'], true)) {
            $paymentId = (string) ($entity['id'] ?? '');
            if (! $gatewayOrderId || $paymentId === '') {
                return 'ignored: missing ids';
            }
            $payment = Payment::query()->where('provider', 'razorpay')->where('provider_order_id', $gatewayOrderId)->first();
            if (! $payment) {
                return 'ignored: unknown order';
            }

            $this->recordSuccessfulPayment(
                $payment->order, $gatewayOrderId, $paymentId, (int) ($entity['amount'] ?? 0), $entity['method'] ?? null, 'webhook'
            );

            return 'paid';
        }

        if ($type === 'payment.failed' && $gatewayOrderId) {
            $payment = Payment::query()->where('provider', 'razorpay')->where('provider_order_id', $gatewayOrderId)->first();
            if ($payment && $payment->status !== 'paid') {
                $payment->forceFill([
                    'error_code' => $entity['error_code'] ?? null,
                    'error_description' => isset($entity['error_description']) ? mb_substr((string) $entity['error_description'], 0, 250) : null,
                ])->save(); // the order stays open: the customer may retry within the expiry window
            }

            return 'failure recorded';
        }

        if ($type === 'refund.processed') {
            return $this->applyRefundWebhook($payload['payload']['refund']['entity'] ?? []);
        }

        return 'ignored: '.$type;
    }

    /** Refunds started in the Razorpay dashboard are mirrored here; ones we started ourselves are skipped. */
    protected function applyRefundWebhook(array $refund): string
    {
        $refundId = (string) ($refund['id'] ?? '');
        $paymentId = (string) ($refund['payment_id'] ?? '');
        $paise = (int) ($refund['amount'] ?? 0);

        $payment = $paymentId ? Payment::query()->where('provider_payment_id', $paymentId)->first() : null;
        if (! $payment || $refundId === '' || $paise <= 0) {
            return 'ignored: unknown refund';
        }

        return DB::transaction(function () use ($payment, $refundId, $paise) {
            $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
            $payment->refresh();
            $known = $payment->meta['refund_ids'] ?? [];

            if (in_array($refundId, $known, true)) {
                return 'refund already recorded';
            }

            $new = min(Money::toPaise($order->grand_total), Money::toPaise($order->refunded_amount) + $paise);
            $order->refunded_amount = Money::fromPaise($new);
            if ($new >= Money::toPaise($order->grand_total)) {
                $order->payment_status = 'refunded';
                $payment->status = 'refunded';
            }
            $order->save();

            $meta = $payment->meta ?? [];
            $meta['refund_ids'] = [...$known, $refundId];
            $payment->meta = $meta;
            $payment->save();

            $this->orders->recordHistory($order, $order->status, $order->status, 'Refund of '.Money::format(Money::fromPaise($paise)).' recorded from gateway', null);

            return 'refund recorded';
        });
    }

    /**
     * Cancel online orders that were never paid within the expiry window and give the stock back.
     * Run by the scheduler (orders:expire-unpaid).
     */
    public function expireUnpaidOrders(?int $minutes = null): int
    {
        $minutes ??= (int) config('arsh.unpaid_order_expiry_minutes', 30);

        $stale = Order::query()
            ->where('payment_method', 'razorpay')
            ->where('payment_status', '!=', 'paid')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->pluck('id');

        $count = 0;
        foreach ($stale as $id) {
            try {
                $this->orders->transition(Order::query()->findOrFail($id), 'cancelled', null, 'Online payment not completed in time');
                $count++;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $count;
    }
}
