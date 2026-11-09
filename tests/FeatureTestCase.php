<?php

namespace Tests;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** Base for HTTP/database tests: fresh in-memory SQLite database with roles seeded. */
abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function customer(): User
    {
        return User::factory()->create();
    }

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** An active product with one purchasable variant. */
    protected function sellableVariant(int $stock = 10, string $price = '100.00'): ProductVariant
    {
        $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE]);

        return ProductVariant::factory()->for($product)->create(['stock' => $stock, 'price' => $price]);
    }

    protected function checkoutData(string $method = 'cod'): array
    {
        return [
            'name' => 'Test Buyer',
            'email' => 'buyer@example.com',
            'phone' => '9876543210',
            'address_line1' => '1 Test Road',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'payment_method' => $method,
        ];
    }

    protected function percentCoupon(string $code = 'TEST10', float $percent = 10): Coupon
    {
        return Coupon::query()->create(['code' => $code, 'type' => 'percent', 'value' => $percent, 'is_active' => true]);
    }
}
