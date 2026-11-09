<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str;
use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Services\PaymentService;
use App\Services\RazorpayService;
use App\Support\Money;
use App\Support\OrderAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $payments,
        protected RazorpayService $razorpay,
    ) {
    }

    /** Pay page: creates the gateway order once and shows Razorpay Checkout (or the simulator in test mode). */
    public function show(Request $request, Order $order): View|RedirectResponse
    {
        abort_unless(OrderAccess::allows($order, $request->user()), 404);

        if ($order->payment_method !== 'razorpay') {
            return redirect()->route('order.success', ['order' => $order->order_number]);
        }
        if ($order->isPaid()) {
            return redirect()->route('order.success', ['order' => $order->order_number]);
        }
        if ($order->status === 'cancelled') {
            return redirect()->route('home')->with('error', 'This order has expired. Please place a new order.');
        }

        try {
            $payment = $this->payments->startOnlinePayment($order);
        } catch (CheckoutException $e) {
            return redirect()->route('checkout.show')->with('error', $e->getMessage());
        }

        return view('pages.pay', [
            'order' => $order,
            'payment' => $payment,
            'mode' => $this->razorpay->mode(),
            'keyId' => $this->razorpay->publicKey(),
            'amountPaise' => Money::toPaise($order->grand_total),
            'simulatedSignature' => $this->razorpay->isSimulated()
                ? fn (string $gwOrder, string $payId) => $this->razorpay->signForSimulation($gwOrder, $payId)
                : null,
        ]);
    }

    /** Browser callback from Razorpay Checkout. Verified server-side before anything is marked paid. */
    public function verify(Request $request, Order $order): JsonResponse
    {
        abort_unless(OrderAccess::allows($order, $request->user()), 404);

        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string', 'max:80'],
            'razorpay_payment_id' => ['required', 'string', 'max:80'],
            'razorpay_signature' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->payments->verifyCheckoutCallback($order, $data['razorpay_order_id'], $data['razorpay_payment_id'], $data['razorpay_signature']);
        } catch (CheckoutException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'redirect' => route('order.success', ['order' => $order->order_number]),
        ]);
    }

    public function failed(Request $request, Order $order): JsonResponse
    {
        abort_unless(OrderAccess::allows($order, $request->user()), 404);
        $this->payments->recordFailedAttempt($order, $request->input('code'), $request->input('description'));

        return response()->json(['ok' => true]);
    }

    /** Test mode only: the simulated gateway "pays" the order through the same verification path. */
    public function simulate(Request $request, Order $order): JsonResponse
    {
        abort_unless($this->razorpay->isSimulated(), 404);
        abort_unless(OrderAccess::allows($order, $request->user()), 404);

        $payment = $this->payments->startOnlinePayment($order);
        $paymentId = 'pay_sim_'.Str::lower(Str::random(14));
        $signature = $this->razorpay->signForSimulation($payment->provider_order_id, $paymentId);

        $simulated = Request::create($request->url(), 'POST', [
            'razorpay_order_id' => $payment->provider_order_id,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
        ]);
        $simulated->setUserResolver(fn () => $request->user());
        $simulated->setLaravelSession($request->session());

        return $this->verify($simulated, $order);
    }
}
