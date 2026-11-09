<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductVariant> */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => '8 ml',
            'size_ml' => 8,
            'sku' => strtoupper(fake()->unique()->bothify('VAR-????-##')),
            'price' => '100.00',
            'sale_price' => null,
            'stock' => 10,
            'low_stock_threshold' => 5,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function unpriced(): static
    {
        return $this->state(fn () => ['price' => null]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }

    public function withStock(int $stock): static
    {
        return $this->state(fn () => ['stock' => $stock]);
    }
}
