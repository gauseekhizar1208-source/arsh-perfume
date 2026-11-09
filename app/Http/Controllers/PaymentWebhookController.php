<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Razorpay webhook. Authenticated by the HMAC of the RAW body (not by CSRF or session).
 * Idempotent by X-Razorpay-Event-Id. Returns 400 for bad signatures, 500 only when a retry may succeed.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(
        protected RazorpayService $razorpay,
        protected PaymentService $payments,
    ) {
    }

    public function handle(Request $request): Response
    {
        $raw = $request->getContent();

        if (! $this->razorpay->verifyWebhookSignature($raw, $request->header('X-Razorpay-Signature'))) {
            return response('invalid signature', 400);
        }

        $eventId = (string) $request->header('X-Razorpay-Event-Id', '');
        if ($eventId === '') {
            return response('missing event id', 400);
        }

        try {
            $outcome = $this->payments->handleWebhook($eventId, $request->json()->all());
        } catch (\Throwable $e) {
            report($e);

            return response('processing error', 500);
        }

        return response($outcome, 200);
    }
}
