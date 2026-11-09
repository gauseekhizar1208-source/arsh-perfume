<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\OrderAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function success(Request $request): View|RedirectResponse
    {
        $order = Order::query()->where('order_number', (string) $request->query('order'))->with('items')->first();
        abort_unless($order && OrderAccess::allows($order, $request->user()), 404);

        return view('pages.order-success', ['order' => $order]);
    }

    /** Signed-in customers: their own orders only. */
    public function index(Request $request): View
    {
        return view('account.orders', [
            'orders' => $request->user()->orders()->withCount('items')->latest('placed_at')->paginate(10),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorize('view', $order);

        return view('account.order', [
            'order' => $order->load(['items', 'statusHistories']),
            'timeline' => $order->timelineSteps(),
        ]);
    }

    public function cancel(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $this->authorize('cancel', $order);

        try {
            $orders->cancelByCustomer($order, $request->user());
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Your order has been cancelled.');
    }
}
