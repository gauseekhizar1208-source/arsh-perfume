<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Support\Money;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Thin Razorpay client (REST via Laravel's HTTP client; no SDK needed).
 *
 * Modes (see config/services.php):
 *   live       real keys configured (rzp_test_* sandbox keys or rzp_live_* production keys)
 *   simulated  no keys, non-production only: a local fake gateway for testing the full checkout flow
 *   disabled   no keys in production: online payment is not offered
 *
 * The simulated gateway signs with a key derived from APP_KEY and goes through exactly the same signature
 * verification code as the real one. It cannot be enabled in production.
 */
class RazorpayService
{
    public function mode(): string
    {
        if (filled(config('services.razorpay.key_id')) && filled(config('services.razorpay.key_secret'))) {
            return 'live';
        }

        return config('services.razorpay.simulate') && ! app()->isProduction() ? 'simulated' : 'disabled';
    }

    public function isLive(): bool
    {
        return $this->mode() === 'live';
    }

    public function isSimulated(): bool
    {
        return $this->mode() === 'simulated';
    }

    /** Is online payment currently offered at checkout (setting on AND a usable gateway)? */
    public function isAvailable(): bool
    {
        return (bool) setting('online_payment_enabled', true) && $this->mode() !== 'disabled';
    }

    /** Public key id for Razorpay Checkout (safe to expose). */
    public function publicKey(): ?string
    {
        return $this->isLive() ? config('services.razorpay.key_id') : 'rzp_simulated';
    }

    /**
     * Create the gateway-side order for an internal order.
     *
     * @return array{id: string, amount: int, currency: string}
     *
     * @throws CheckoutException
     */
    public function createGatewayOrder(Order $order): array
    {
        $amount = Money::toPaise($order->grand_total);

        if ($this->isSimulated()) {
            return ['id' => 'order_sim_'.Str::lower(Str::random(14)), 'amount' => $amount, 'currency' => 'INR'];
        }
        if (! $this->isLive()) {
            throw new CheckoutException('Online payment is not available right now. Please choose Cash on Delivery.');
        }

        try {
            $response = $this->client()->post('/orders', [
                'amount' => $amount,
                'currency' => config('services.razorpay.currency', 'INR'),
                'receipt' => $order->order_number,
                'notes' => ['order_number' => $order->order_number],
            ])->throw();
        } catch (Throwable $e) {
            report($e);
            throw new CheckoutException('We could not start the online payment. Please try again or choose Cash on Delivery.');
        }

        return [
            'id' => (string) $response->json('id'),
            'amount' => (int) $response->json('amount'),
            'currency' => (string) $response->json('currency'),
        ];
    }

    /** Checkout.js callback signature: HMAC-SHA256(order_id|payment_id) with the key secret. */
    public function verifySignature(string $gatewayOrderId, string $paymentId, string $signature): bool
    {
        $secret = $this->signingSecret();
        if (! $secret || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $gatewayOrderId.'|'.$paymentId, $secret), $signature);
    }

    /** Only for the simulated gateway. */
    public function signForSimulation(string $gatewayOrderId, string $paymentId): string
    {
        abort_unless($this->isSimulated(), 404);

        return hash_hmac('sha256', $gatewayOrderId.'|'.$paymentId, $this->signingSecret());
    }

    /** Webhook signature: HMAC-SHA256 of the RAW request body with the webhook secret. */
    public function verifyWebhookSignature(string $rawBody, ?string $signature): bool
    {
        $secret = config('services.razorpay.webhook_secret');
        if (blank($secret) || blank($signature)) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    /** @return array<string, mixed> payment entity from the gateway */
    public function fetchPayment(string $paymentId): array
    {
        try {
            return $this->client()->get('/payments/'.$paymentId)->throw()->json();
        } catch (Throwable $e) {
            report($e);
            throw new CheckoutException('We could not confirm your payment with the gateway yet. If money was deducted it will be reconciled automatically.');
        }
    }

    /** @return array<string, mixed> */
    public function capture(string $paymentId, int $amountPaise): array
    {
        try {
            return $this->client()->post("/payments/$paymentId/capture", [
                'amount' => $amountPaise,
                'currency' => config('services.razorpay.currency', 'INR'),
            ])->throw()->json();
        } catch (Throwable $e) {
            report($e);
            throw new CheckoutException('We could not capture the payment. Please contact support with your order number.');
        }
    }

    /**
     * Refund (live mode). Returns the gateway refund entity. In simulated mode a fake refund id is returned.
     *
     * @return array{id: string}
     *
     * @throws CheckoutException
     */
    public function refund(string $paymentId, int $amountPaise, string $receipt): array
    {
        if ($this->isSimulated()) {
            return ['id' => 'rfnd_sim_'.Str::lower(Str::random(12))];
        }

        try {
            $json = $this->client()->post("/payments/$paymentId/refund", [
                'amount' => $amountPaise,
                'receipt' => Str::limit($receipt, 40, ''),
            ])->throw()->json();

            return ['id' => (string) ($json['id'] ?? '')];
        } catch (RequestException $e) {
            report($e);
            throw new CheckoutException('The gateway rejected the refund: '.($e->response?->json('error.description') ?? 'unknown error'));
        } catch (Throwable $e) {
            report($e);
            throw new CheckoutException('The refund could not be sent to the gateway. Please try again.');
        }
    }

    protected function signingSecret(): ?string
    {
        if ($this->isLive()) {
            return config('services.razorpay.key_secret');
        }
        if ($this->isSimulated()) {
            return hash('sha256', (string) config('app.key').'|razorpay-simulated');
        }

        return null;
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl(config('services.razorpay.api_base'))
            ->withBasicAuth((string) config('services.razorpay.key_id'), (string) config('services.razorpay.key_secret'))
            ->acceptJson()
            ->asJson()
            ->timeout(20);
    }
}
