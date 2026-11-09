<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/** Direct UPI payments are confirmed by an administrator, never by the customer callback. */
class UpiPaymentService
{
    public const REFERENCE_RULE = 'regex:/^[A-Za-z0-9]{6,64}$/';

    public function isAvailable(): bool
    {
        return (bool) setting('upi_enabled', false)
            && preg_match('/^[A-Za-z0-9._-]{2,128}@[A-Za-z0-9.-]{2,64}$/', (string) setting('upi_id')) === 1
            && filled(setting('upi_payee_name'));
    }

    public function snapshot(): array
    {
        return ['payee_name' => trim((string) setting('upi_payee_name')), 'upi_id' => trim((string) setting('upi_id'))];
    }

    public function payment(Order $order): Payment
    {
        return $order->payments()->where('provider', 'upi')->firstOrFail();
    }

    public function paymentUri(Order $order, Payment $payment): string
    {
        $details = $payment->meta['upi'] ?? [];

        return 'upi://pay?'.http_build_query([
            'pa' => $details['upi_id'] ?? '',
            'pn' => $details['payee_name'] ?? '',
            'am' => Money::fromPaise(Money::toPaise($order->grand_total)),
            'cu' => 'INR',
            'tn' => 'Arsh order '.$order->order_number,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** A reference is only a customer claim; it does not change either payment status. */
    public function submitReference(Order $order, string $reference): void
    {
        $reference = $this->normaliseReference($reference);
        DB::transaction(function () use ($order, $reference) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->assertPending($locked);
            $payment = Payment::query()->where('order_id', $locked->id)->where('provider', 'upi')->lockForUpdate()->firstOrFail();
            $meta = $payment->meta ?? [];
            if (($meta['customer_reference'] ?? null) === $reference) {
                return;
            }
            $payment->meta = array_merge($meta, ['customer_reference' => $reference, 'submitted_at' => now()->toIso8601String()]);
            $payment->save();
            $locked->statusHistories()->create([
                'from_status' => $locked->status, 'to_status' => $locked->status,
                'note' => 'Customer submitted UPI reference (unverified): '.$reference,
            ]);
        });
    }

    public function verifyReceipt(Order $order, string $reference, User $actor): Order
    {
        abort_unless($actor->isAdmin(), 403);
        $reference = $this->normaliseReference($reference);
        $paymentId = 'upi:'.$reference;

        try {
            return DB::transaction(function () use ($order, $reference, $paymentId, $actor) {
                $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($locked->payment_method !== 'upi' || in_array($locked->status, ['cancelled', 'returned'], true)) {
                    throw new CheckoutException('This order cannot be marked as a received UPI payment.');
                }
                $payment = Payment::query()->where('order_id', $locked->id)->where('provider', 'upi')->lockForUpdate()->firstOrFail();
                if ($locked->payment_status === 'paid' && $payment->provider_payment_id === $paymentId) {
                    return $locked;
                }
                $this->assertPending($locked);
                if (Payment::query()->where('provider_payment_id', $paymentId)->whereKeyNot($payment->id)->exists()) {
                    throw new CheckoutException('This UPI transaction reference is already used for another order.');
                }
                $payment->forceFill([
                    'provider_payment_id' => $paymentId, 'method' => 'upi', 'status' => 'paid', 'verified_at' => now(),
                    'meta' => array_merge($payment->meta ?? [], ['verified_reference' => $reference, 'verified_by' => $actor->id]),
                ])->save();
                $locked->forceFill(['payment_status' => 'paid', 'paid_at' => now()])->save();
                $locked->statusHistories()->create([
                    'from_status' => $locked->status, 'to_status' => $locked->status,
                    'note' => 'UPI receipt manually verified. Reference: '.$reference, 'changed_by' => $actor->id,
                ]);

                return $locked;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw new CheckoutException('This UPI transaction reference is already used for another order.');
        }
    }

    private function assertPending(Order $order): void
    {
        if ($order->payment_method !== 'upi' || $order->payment_status !== 'pending' || $order->status !== 'pending') {
            throw new CheckoutException('This order is no longer awaiting a UPI payment.');
        }
    }

    private function normaliseReference(string $reference): string
    {
        $reference = strtoupper(trim($reference));
        if (! preg_match('/^[A-Z0-9]{6,64}$/', $reference)) {
            throw new CheckoutException('Enter the transaction reference from your UPI payment receipt (6–64 letters or digits).');
        }

        return $reference;
    }
}
