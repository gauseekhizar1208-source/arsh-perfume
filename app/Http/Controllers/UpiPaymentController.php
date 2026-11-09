<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Services\UpiPaymentService;
use App\Support\OrderAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UpiPaymentController extends Controller
{
    public function show(Request $request, Order $order, UpiPaymentService $upi): View|RedirectResponse
    {
        abort_unless(OrderAccess::allows($order, $request->user()) && $order->payment_method === 'upi', 404);
        if ($order->payment_status !== 'pending' || $order->status !== 'pending') {
            return redirect()->route('order.success', ['order' => $order->order_number]);
        }
        $payment = $upi->payment($order);

        return view('pages.upi-pay', ['order' => $order, 'payment' => $payment, 'upiDetails' => $payment->meta['upi'], 'paymentUri' => $upi->paymentUri($order, $payment)]);
    }

    public function submitReference(Request $request, Order $order, UpiPaymentService $upi): RedirectResponse
    {
        abort_unless(OrderAccess::allows($order, $request->user()) && $order->payment_method === 'upi', 404);
        $data = $request->validate(['reference' => ['required', 'string', UpiPaymentService::REFERENCE_RULE]]);
        try {
            $upi->submitReference($order, $data['reference']);
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Reference submitted. Your payment is pending manual verification. Please do not pay again.');
    }
}
