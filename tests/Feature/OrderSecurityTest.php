<?php

namespace Tests\Feature;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Services\OrderService;
use Tests\FeatureTestCase;

class OrderSecurityTest extends FeatureTestCase
{
    private function placeOrderFor(\App\Models\User $user): Order
    {
        $variant = $this->sellableVariant(stock: 5);
        $this->actingAs($user)->post('/cart/items', ['variant_id' => $variant->id]);
        $this->actingAs($user)->post('/checkout', $this->checkoutData());

        return Order::query()->where('user_id', $user->id)->firstOrFail();
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $owner = $this->customer();
        $order = $this->placeOrderFor($owner);

        $this->actingAs($this->customer())->get('/account/orders/'.$order->order_number)->assertForbidden();
        $this->actingAs($owner)->get('/account/orders/'.$order->order_number)->assertOk();
    }

    public function test_customer_cannot_cancel_another_customers_order(): void
    {
        $order = $this->placeOrderFor($this->customer());

        $this->actingAs($this->customer())->post('/account/orders/'.$order->order_number.'/cancel')->assertForbidden();
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_guest_order_page_is_visible_only_to_the_browser_that_placed_it(): void
    {
        $variant = $this->sellableVariant(stock: 5);
        $this->post('/cart/items', ['variant_id' => $variant->id]);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::query()->firstOrFail();

        // Same session (the placing browser) may see the confirmation.
        $this->get('/order-success?order='.$order->order_number)->assertOk();

        // A different visitor (fresh session) may not.
        $this->flushSession();
        $this->get('/order-success?order='.$order->order_number)->assertNotFound();
    }

    public function test_order_cannot_jump_straight_to_delivered(): void
    {
        $order = $this->placeOrderFor($this->customer());

        $this->expectException(CheckoutException::class);
        app(OrderService::class)->transition($order, 'delivered', $this->admin());
    }

    public function test_cancelling_restores_stock_exactly_once(): void
    {
        $variant = $this->sellableVariant(stock: 5);
        $user = $this->customer();
        $this->actingAs($user)->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2]);
        $this->actingAs($user)->post('/checkout', $this->checkoutData());
        $order = Order::query()->firstOrFail();
        $this->assertSame(3, $variant->fresh()->stock);

        $service = app(OrderService::class);
        $service->transition($order, 'cancelled', $this->admin(), 'test');
        $this->assertSame(5, $variant->fresh()->stock);

        // A second cancellation is not a valid transition and must not restore stock again.
        $this->expectException(CheckoutException::class);
        try {
            $service->transition($order->fresh(), 'cancelled', $this->admin());
        } finally {
            $this->assertSame(5, $variant->fresh()->stock);
        }
    }
}
