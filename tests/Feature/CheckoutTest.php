<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use Tests\FeatureTestCase;

class CheckoutTest extends FeatureTestCase
{
    public function test_adding_more_than_stock_is_refused(): void
    {
        $variant = $this->sellableVariant(stock: 2);

        $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 3])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_cash_on_delivery_order_reduces_stock_and_clears_cart(): void
    {
        $variant = $this->sellableVariant(stock: 10, price: '100.00');

        $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2]);
        $response = $this->post('/checkout', $this->checkoutData('cod'));

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('order.success', ['order' => $order->order_number]));

        $this->assertSame('pending', $order->status);
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('200.00', $order->grand_total);
        $this->assertSame(8, $variant->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('inventory_movements', ['product_variant_id' => $variant->id, 'type' => 'sale', 'quantity_change' => -2]);
    }

    public function test_order_item_keeps_price_snapshot(): void
    {
        $variant = $this->sellableVariant(stock: 5, price: '100.00');
        $this->post('/cart/items', ['variant_id' => $variant->id]);
        $this->post('/checkout', $this->checkoutData());

        // Owner changes the price later: the historical order must not change.
        $variant->update(['price' => '250.00']);

        $item = Order::query()->firstOrFail()->items()->firstOrFail();
        $this->assertSame('100.00', $item->unit_price);
        $this->assertSame('100.00', $item->line_total);
    }

    public function test_stock_removed_before_checkout_blocks_the_order(): void
    {
        $variant = $this->sellableVariant(stock: 1);
        $this->post('/cart/items', ['variant_id' => $variant->id]);

        // Another buyer takes the last unit (simulated directly in the database).
        $variant->update(['stock' => 0]);

        $this->post('/checkout', $this->checkoutData())->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(0, $variant->fresh()->stock);
    }

    public function test_percentage_coupon_reduces_the_total(): void
    {
        $variant = $this->sellableVariant(stock: 10, price: '100.00');
        $this->percentCoupon('TEST10', 10);

        $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2]);
        $this->post('/cart/coupon', ['code' => 'test10']);
        $this->post('/checkout', $this->checkoutData());

        $order = Order::query()->firstOrFail();
        $this->assertSame('20.00', $order->discount_total);
        $this->assertSame('180.00', $order->grand_total);
        $this->assertSame('TEST10', $order->coupon_code);
        $this->assertDatabaseHas('coupon_usages', ['order_id' => $order->id]);
        $this->assertSame(1, Coupon::query()->where('code', 'TEST10')->firstOrFail()->used_count);
    }

    public function test_expired_coupon_is_refused(): void
    {
        $variant = $this->sellableVariant();
        Coupon::query()->create([
            'code' => 'OLD', 'type' => 'fixed', 'value' => 20, 'is_active' => true, 'expires_at' => now()->subDay(),
        ]);

        $this->post('/cart/items', ['variant_id' => $variant->id]);
        $this->post('/cart/coupon', ['code' => 'OLD'])->assertSessionHas('coupon_error');

        $this->assertNull(\App\Models\Cart::query()->first()?->coupon_code);
    }

    public function test_unavailable_variant_cannot_be_added(): void
    {
        $variant = $this->sellableVariant();
        $variant->update(['price' => null]);

        $this->post('/cart/items', ['variant_id' => $variant->id])->assertSessionHas('error');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_variant_is_not_sellable_when_product_is_draft(): void
    {
        $variant = $this->sellableVariant();
        $variant->product->update(['status' => 'draft']);

        $this->post('/cart/items', ['variant_id' => $variant->id])->assertSessionHas('error');
    }
}
