<?php

namespace Tests\Feature;

use App\Models\Order;
use Carbon\Carbon;
use Tests\FeatureTestCase;

class AdminDashboardTest extends FeatureTestCase
{
    public function test_empty_dashboard_renders_navigation_actions_and_accessible_chart(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertOk()
            ->assertSee('Your store,')
            ->assertSee('admin-app')
            ->assertSee('aria-current="page"', false)
            ->assertSee('aria-controls="admin-sidebar"', false)
            ->assertSee('View chart data')
            ->assertSee('Your first order is a beginning.')
            ->assertSee(route('admin.products.create'))
            ->assertSee(route('admin.inventory.index', ['stock' => 'low']), false);
    }

    public function test_dashboard_keeps_lifetime_metrics_and_selected_chart_period_distinct(): void
    {
        $this->travelTo(Carbon::parse('2026-10-22 12:00:00'));
        $admin = $this->admin();
        $admin->update(['name' => 'Arsh Admin']);
        $customer = $this->customer();
        $variant = $this->sellableVariant(stock: 3, price: '180.00');
        $variant->product->update(['name' => 'Amber Attar']);
        $this->sellableVariant(stock: 0);

        foreach ([180, 320, 240, 480, 360] as $i => $amount) {
            $order = Order::create(array_merge($this->checkoutData(), [
                'order_number' => 'AP-PREVIEW-'.($i + 1),
                'user_id' => $customer->id,
                'name' => 'Sample Customer',
                'email' => 'sample@example.invalid',
                'subtotal' => $amount,
                'grand_total' => $amount,
                'refunded_amount' => $i === 4 ? 20 : 0,
                'payment_status' => 'paid',
                'status' => $i === 4 ? 'pending' : 'delivered',
                'placed_at' => now()->subDays(4 - $i),
            ]));
            $order->items()->create([
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => '8 ml',
                'sku' => $variant->sku,
                'list_price' => $amount,
                'unit_price' => $amount,
                'quantity' => 1,
                'line_total' => $amount,
            ]);
        }

        $response = $this->actingAs($admin)->get('/admin?from=2026-10-21&to=2026-10-22');
        $response->assertOk()->assertViewHas('stats', fn ($stats) =>
            $stats['total_revenue'] === '1560.00'
            && $stats['total_orders'] === 5
            && $stats['low_stock_products'] === 1
            && $stats['out_of_stock_products'] === 1
        )->assertViewHas('chart', fn ($chart) =>
            $chart['revenue'] === [480.0, 340.0]
            && $chart['orders'] === [1, 1]
        )->assertSee('AP-PREVIEW-5')->assertSee('Amber Attar')
            ->assertSee('value="2026-10-21"', false)
            ->assertSee('value="2026-10-22"', false);

        // Optional local browser fixture: test data only, never a public route or real account.
        if (getenv('ARSH_CAPTURE_ADMIN_PREVIEW') === '1') {
            foreach (['overview' => '/admin', 'products' => '/admin/products', 'product-form' => '/admin/products/create', 'orders' => '/admin/orders'] as $name => $path) {
                $page = $this->get($path)->assertOk();
                file_put_contents(storage_path('framework/admin-preview-'.$name.'.html'),
                    preg_replace('#http://localhost(?::\d+)?/build/#', 'http://127.0.0.1:8012/build/', $page->getContent()));
            }
        }
    }
}
