<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(config('arsh.order_statuses')))],
            'payment_status' => ['nullable', 'in:pending,paid,failed,refunded'],
            'payment_method' => ['nullable', Rule::in(array_keys(config('arsh.payment_methods')))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $orders = $this->filtered($f)->withCount('items')->latest('placed_at')->paginate(20)->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $f,
            'statuses' => config('arsh.order_statuses'),
        ]);
    }

    public function show(Order $order): View
    {
        return view('admin.orders.show', [
            'order' => $order->load(['items', 'payments', 'statusHistories.changedBy:id,name', 'coupon:id,code']),
            'statuses' => config('arsh.order_statuses'),
            'allowed' => config("arsh.status_transitions.{$order->status}", []),
        ]);
    }

    public function invoice(Order $order): View
    {
        return view('admin.orders.invoice', ['order' => $order->load('items')]);
    }

    public function updateStatus(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(config('arsh.order_statuses')))],
            'note' => ['nullable', 'string', 'max:250'],
            'restock' => ['sometimes', 'boolean'],
        ]);

        try {
            $orders->transition($order, $data['status'], $request->user(), $data['note'] ?? null, ['restock' => (bool) ($data['restock'] ?? false)]);
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLogger::log('order.status_changed', $order, "Order {$order->order_number} changed to {$data['status']}");

        return back()->with('status', 'Order status updated.');
    }

    public function updateTracking(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'courier_name' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100', 'alpha_dash'],
            'tracking_url' => ['nullable', 'url:https', 'max:255'],
        ]);
        $order->update($data);
        ActivityLogger::log('order.tracking_updated', $order, "Tracking updated for {$order->order_number}");

        return back()->with('status', 'Shipment details saved.');
    }

    public function markPaid(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        try {
            $orders->markCodPaid($order, $request->user());
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }
        ActivityLogger::log('order.cod_collected', $order, "COD collected for {$order->order_number}");

        return back()->with('status', 'Payment marked as received.');
    }

    public function refund(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'note' => ['required', 'string', 'max:250'],
        ]);

        try {
            $orders->refund($order, $data['amount'] ?? null, $request->user(), $data['note']);
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }
        ActivityLogger::log('order.refund', $order, "Refund recorded for {$order->order_number}", ['amount' => $data['amount'] ?? 'full']);

        return back()->with('status', 'Refund recorded.');
    }

    public function verifyUpi(Request $request, Order $order, \App\Services\UpiPaymentService $upi): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', \App\Services\UpiPaymentService::REFERENCE_RULE],
            'receipt_verified' => ['required', 'accepted'],
        ]);
        try {
            $upi->verifyReceipt($order, $data['reference'], $request->user());
        } catch (CheckoutException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        ActivityLogger::log('order.upi_verified', $order, "UPI receipt verified for {$order->order_number}");

        return back()->with('status', 'UPI payment verified. You can now confirm and fulfil the order.');
    }

    public function export(Request $request): StreamedResponse
    {
        $f = $request->validate([
            'status' => ['nullable', 'string', 'max:24'],
            'payment_status' => ['nullable', 'string', 'max:24'],
            'payment_method' => ['nullable', 'string', 'max:24'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return response()->streamDownload(function () use ($f) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['order_number', 'placed_at', 'status', 'payment_method', 'payment_status', 'name', 'email', 'phone', 'city', 'state', 'postal_code', 'subtotal', 'discount', 'shipping', 'grand_total', 'refunded', 'courier', 'tracking']);
            $this->filtered($f)->orderBy('id')->chunk(300, function ($orders) use ($out) {
                foreach ($orders as $o) {
                    fputcsv($out, [$o->order_number, $o->placed_at, $o->status, $o->payment_method, $o->payment_status, $o->name, $o->email, $o->phone, $o->city, $o->state, $o->postal_code, $o->subtotal, $o->discount_total, $o->shipping_total, $o->grand_total, $o->refunded_amount, $o->courier_name, $o->tracking_number]);
                }
            });
            fclose($out);
        }, 'arsh-orders-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    protected function filtered(array $f)
    {
        return Order::query()
            ->when($f['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('order_number', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('name', 'like', $like));
            })
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['payment_status'] ?? null, fn ($q, $s) => $q->where('payment_status', $s))
            ->when($f['payment_method'] ?? null, fn ($q, $m) => $q->where('payment_method', $m))
            ->when($f['from'] ?? null, fn ($q, $d) => $q->whereDate('placed_at', '>=', $d))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->whereDate('placed_at', '<=', $d));
    }
}
