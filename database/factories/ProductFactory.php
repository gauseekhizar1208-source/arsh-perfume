<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'category_id' => Category::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-????-##')),
            'name' => ucwords($name),
            'slug' => null, // generated below
            'short_description' => 'Test product description.',
            'description' => 'Test product description used by the automated tests.',
            'status' => Product::STATUS_ACTIVE,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Product $product) {
            $product->slug ??= \Illuminate\Support\Str::slug($product->name).'-'.fake()->unique()->numerify('###');
        });
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Product::STATUS_DRAFT]);
    }
}
