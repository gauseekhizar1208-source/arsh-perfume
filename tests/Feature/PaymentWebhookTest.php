<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use Tests\FeatureTestCase;

/**
 * Runs in the simulated gateway mode (no Razorpay keys, non-production). The webhook path under test is the
 * same code that handles live webhooks; only the signing secret differs.
 */
class PaymentWebhookTest extends FeatureTestCase
{
    private const SECRET = 'whsec_test_secret'; // phpunit.xml RAZORPAY_WEBHOOK_SECRET

    /** Creates an unpaid Razorpay order and returns [order, gatewayOrderId]. */
    private function onlineOrder(): array
    {
        $variant = $this->sellableVariant(stock: 5, price: '100.00');
        $user = $this->customer();
        $this->actingAs($user)->post('/cart/items', ['variant_id' => $variant->id]);
        $this->actingAs($user)->post('/checkout', $this->checkoutData('razorpay'));

        $order = Order::query()->firstOrFail();
        $this->actingAs($user)->get('/checkout/pay/'.$order->order_number)->assertOk();

        return [$order->fresh(), Payment::query()->where('order_id', $order->id)->firstOrFail()->provider_order_id];
    }

    private function send(string $eventId, array $payload, ?string $signature = null)
    {
        $raw = json_encode($payload);
        $signature ??= hash_hmac('sha256', $raw, self::SECRET);

        return $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
            'HTTP_X_RAZORPAY_EVENT_ID' => $eventId,
        ], $raw);
    }

    private function capturedPayload(string $gatewayOrderId, int $amountPaise): array
    {
        return [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_test_001', 'order_id' => $gatewayOrderId, 'amount' => $amountPaise, 'method' => 'upi',
            ]]],
        ];
    }

    public function test_webhook_with_bad_signature_is_rejected_and_order_stays_unpaid(): void
    {
        [$order, $gw] = $this->onlineOrder();

        $this->send('evt_bad', $this->capturedPayload($gw, 10000), 'not-the-signature')->assertStatus(400);

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_valid_capture_marks_order_paid_once(): void
    {
        [$order, $gw] = $this->onlineOrder();
        $payload = $this->capturedPayload($gw, 10000); // ₹100.00 in paise

        $this->send('evt_001', $payload)->assertOk()->assertSee('paid');
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->status);

        $historyCount = OrderStatusHistory::query()->where('order_id', $order->id)->count();

        // The same event delivered again (Razorpay retries) must not change anything.
        $this->send('evt_001', $payload)->assertOk()->assertSee('duplicate');
        // A different event id for the same successful payment must not double-count either.
        $this->send('evt_002', $payload)->assertOk();

        $this->assertSame($historyCount, OrderStatusHistory::query()->where('order_id', $order->id)->count());
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_capture_with_wrong_amount_is_recorded_but_does_not_pay_the_order(): void
    {
        [$order, $gw] = $this->onlineOrder();

        $this->send('evt_wrong', $this->capturedPayload($gw, 100))->assertOk()->assertSee('rejected');

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('pending', Payment::query()->where('order_id', $order->id)->firstOrFail()->status);
    }

    public function test_checkout_callback_with_forged_signature_is_refused(): void
    {
        [$order, $gw] = $this->onlineOrder();
        $user = $order->user;

        $this->actingAs($user)->postJson('/checkout/pay/'.$order->order_number.'/verify', [
            'razorpay_order_id' => $gw,
            'razorpay_payment_id' => 'pay_forged',
            'razorpay_signature' => str_repeat('a', 64),
        ])->assertStatus(422);

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_simulated_checkout_succeeds_through_the_same_verification(): void
    {
        [$order] = $this->onlineOrder();

        $this->actingAs($order->user)->postJson('/checkout/pay/'.$order->order_number.'/simulate')
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame('paid', $order->fresh()->payment_status);
    }
}
