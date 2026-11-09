<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\FeatureTestCase;

class AdminProductTest extends FeatureTestCase
{
    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Oud Attar',
            'sku' => 'TEST-OUD-8',
            'status' => 'draft',
            'category_id' => Category::factory()->create()->id,
            'variants' => [
                ['name' => '8 ml', 'sku' => 'TEST-OUD-8-V1', 'size_ml' => 8, 'price' => '119.00', 'stock' => 4, 'is_active' => '1'],
            ],
        ], $overrides);
    }

    public function test_admin_creates_draft_product_with_generated_slug_and_stock(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/products', $this->productPayload())
            ->assertRedirect();

        $product = Product::query()->where('sku', 'TEST-OUD-8')->firstOrFail();
        $this->assertSame('test-oud-attar', $product->slug);
        $this->assertSame(Product::STATUS_DRAFT, $product->status);
        $this->assertSame(4, $product->variants()->firstOrFail()->stock);
        $this->assertDatabaseHas('inventory_movements', ['product_variant_id' => $product->variants()->first()->id, 'quantity_change' => 4]);
    }

    public function test_product_cannot_be_published_without_a_price(): void
    {
        $payload = $this->productPayload(['status' => 'active']);
        $payload['variants'][0]['price'] = null;

        $this->actingAs($this->admin())->post('/admin/products', $payload)
            ->assertSessionHasErrors('status');

        $this->assertDatabaseMissing('products', ['sku' => 'TEST-OUD-8']);
    }

    public function test_sale_price_must_be_below_regular_price(): void
    {
        $payload = $this->productPayload();
        $payload['variants'][0]['sale_price'] = '150.00';

        $this->actingAs($this->admin())->post('/admin/products', $payload)
            ->assertSessionHasErrors('variants.0.sale_price');
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');
        $payload = $this->productPayload(['images' => [UploadedFile::fake()->create('evil.php', 4, 'application/x-php')]]);

        $this->actingAs($this->admin())->post('/admin/products', $payload)
            ->assertSessionHasErrors('images.0');

        Storage::disk('public')->assertDirectoryEmpty('products');
    }

    public function test_valid_image_is_stored_with_random_name(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('photo.jpg', 800, 800);

        $this->actingAs($this->admin())->post('/admin/products', $this->productPayload(['images' => [$file]]))
            ->assertSessionHasNoErrors();

        $image = Product::query()->where('sku', 'TEST-OUD-8')->firstOrFail()->images()->firstOrFail();
        $this->assertStringNotContainsString('photo', $image->path);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_customer_cannot_create_products(): void
    {
        $this->actingAs($this->customer())->post('/admin/products', $this->productPayload())
            ->assertForbidden();
    }
}
