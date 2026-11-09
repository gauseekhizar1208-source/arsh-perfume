<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\UpiPaymentService;
use Tests\FeatureTestCase;

class UpiPaymentTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('upi_enabled', true);
        Setting::set('upi_id', 'test.merchant@upi');
        Setting::set('upi_payee_name', 'Test Merchant');
    }

    private function placeUpi(): Order
    {
        $variant = $this->sellableVariant(stock: 5, price: '125.50');
        $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])->assertSessionHasNoErrors();
        $this->get('/checkout')->assertOk()->assertSee('Pay by UPI QR');
        $response = $this->post('/checkout', $this->checkoutData('upi'));
        $order = Order::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('checkout.upi', $order));

        return $order;
    }

    public function test_order_reserves_stock_and_snapshots_upi_destination_and_exact_total(): void
    {
        $order = $this->placeUpi();
        $this->assertSame('251.00', $order->grand_total);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('created', $order->payments()->firstOrFail()->status);
        $this->assertSame(3, $order->items->first()->variant->stock);
        $this->assertDatabaseCount('cart_items', 0);
        Setting::set('upi_id', 'changed.account@upi');
        $upi = app(UpiPaymentService::class);
        $payment = $upi->payment($order);
        parse_str(substr($upi->paymentUri($order, $payment), strlen('upi://pay?')), $payload);
        $this->assertSame('test.merchant@upi', $payload['pa']);
        $this->assertSame('Test Merchant', $payload['pn']);
        $this->assertSame('251.00', $payload['am']);
        $this->assertSame('INR', $payload['cu']);
        $this->get(route('checkout.upi', $order))->assertOk()->assertSee('data-upi-qr', false)->assertSee('test.merchant@upi');
        if (getenv('ARSH_CAPTURE_UPI_PREVIEW') === '1') {
            $page = $this->get(route('checkout.upi', $order));
            file_put_contents(storage_path('framework/upi-preview.html'), preg_replace('#http://localhost(?::\d+)?/build/#', 'http://127.0.0.1:8012/build/', $page->getContent()));
            $this->actingAs($this->admin());
            $adminPage = $this->get(route('admin.orders.show', $order))->assertOk();
            file_put_contents(storage_path('framework/upi-admin-preview.html'), preg_replace('#http://localhost(?::\d+)?/build/#', 'http://127.0.0.1:8012/build/', $adminPage->getContent()));
        }
    }

    public function test_reference_submission_never_marks_payment_paid_and_hides_repeat_payment_qr(): void
    {
        $order = $this->placeUpi();
        $url = route('checkout.upi.reference', $order);
        $this->post($url, ['reference' => '123456789012', 'payment_status' => 'paid'])->assertSessionHas('status');
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('created', $order->payments()->firstOrFail()->status);
        $this->assertSame('123456789012', $order->payments()->firstOrFail()->meta['customer_reference']);
        $this->get(route('checkout.upi', $order))->assertOk()->assertSee('Reference received')->assertDontSee('data-upi-qr', false);
        $this->post($url, ['reference' => '<script>'])->assertSessionHasErrors('reference');
    }

    public function test_other_browsers_cannot_view_or_submit_a_reference_for_an_order(): void
    {
        $order = $this->placeUpi();
        $this->flushSession();
        $this->get(route('checkout.upi', $order))->assertNotFound();
        $this->post(route('checkout.upi.reference', $order), ['reference' => '123456789012'])->assertNotFound();
        $this->actingAs($this->customer())->get(route('checkout.upi', $order))->assertNotFound();
    }

    public function test_only_admin_can_verify_and_fulfil_an_unpaid_upi_order(): void
    {
        $order = $this->placeUpi();
        $verify = route('admin.orders.verify-upi', $order);
        $this->actingAs($this->customer())->post($verify, ['reference' => '123456789012', 'receipt_verified' => 1])->assertForbidden();
        $this->actingAs($this->admin());
        $this->patch(route('admin.orders.status', $order), ['status' => 'confirmed'])->assertSessionHas('error');
        $this->assertSame('pending', $order->fresh()->status);
        $this->post($verify, ['reference' => '123456789012'])->assertSessionHasErrors('receipt_verified');
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->post($verify, ['reference' => '123456789012', 'receipt_verified' => 1])->assertSessionHas('status');
        $this->assertSame('paid', $order->fresh()->payment_status);
        $payment = $order->payments()->firstOrFail();
        $this->assertSame('paid', $payment->status);
        $this->assertSame('upi:123456789012', $payment->provider_payment_id);
        $this->assertNotNull($payment->verified_at);
        $historyCount = $order->statusHistories()->count();
        $this->post($verify, ['reference' => '123456789012', 'receipt_verified' => 1]);
        $this->assertSame($historyCount, $order->statusHistories()->count());
        $this->patch(route('admin.orders.status', $order), ['status' => 'confirmed'])->assertSessionHas('status');
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_the_same_bank_reference_cannot_settle_two_orders(): void
    {
        $first = $this->placeUpi();
        $second = $this->placeUpi();
        $this->actingAs($this->admin());
        $this->post(route('admin.orders.verify-upi', $first), ['reference' => 'bank123456', 'receipt_verified' => 1])->assertSessionHas('status');
        $this->post(route('admin.orders.verify-upi', $second), ['reference' => 'BANK123456', 'receipt_verified' => 1])->assertSessionHas('error');
        $this->assertSame('pending', $second->fresh()->payment_status);
        $this->assertSame(1, Payment::query()->where('status', 'paid')->count());
    }

    public function test_cancellation_restores_stock_and_prevents_later_upi_confirmation(): void
    {
        $order = $this->placeUpi();
        $this->actingAs($this->admin());
        $this->patch(route('admin.orders.status', $order), ['status' => 'cancelled'])->assertSessionHas('status');
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame(5, $order->items->first()->variant->fresh()->stock);
        $this->post(route('admin.orders.verify-upi', $order), ['reference' => '123456789012', 'receipt_verified' => 1])->assertSessionHas('error');
        $this->assertSame('failed', $order->fresh()->payment_status);
    }

    public function test_disabled_upi_is_not_offered_or_accepted(): void
    {
        Setting::set('upi_enabled', false);
        $variant = $this->sellableVariant();
        $this->post('/cart/items', ['variant_id' => $variant->id]);
        $this->get('/checkout')->assertOk()->assertDontSee('Pay by UPI QR');
        $this->post('/checkout', $this->checkoutData('upi'))->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_upi_settings_validate_destination_before_enabling(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update'), ['upi_enabled' => 1, 'upi_id' => 'not-an-id', 'upi_payee_name' => ''])->assertSessionHasErrors(['upi_id', 'upi_payee_name']);
        $this->get(route('admin.settings.edit'))->assertOk()->assertSee('Receiving UPI ID');
    }

    public function test_manual_upi_refund_preserves_receipt_metadata_without_calling_gateway(): void
    {
        $order = $this->placeUpi();
        $this->actingAs($this->admin());
        $this->post(route('admin.orders.verify-upi', $order), ['reference' => '123456789012', 'receipt_verified' => 1])->assertSessionHas('status');
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        $this->post(route('admin.orders.refund', $order), ['note' => 'Refund transferred separately through bank'])->assertSessionHas('status');
        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertSame('251.00', $order->fresh()->refunded_amount);
        $this->assertSame('123456789012', $order->payments()->firstOrFail()->meta['verified_reference']);
    }

    public function test_upi_pending_manual_review_is_not_expired_as_a_razorpay_attempt(): void
    {
        $order = $this->placeUpi();
        $this->post(route('checkout.upi.reference', $order), ['reference' => '123456789012']);
        $this->travel(2)->hours();
        $this->assertSame(0, app(\App\Services\PaymentService::class)->expireUnpaidOrders());
        $this->assertSame('pending', $order->fresh()->status);
    }
}
