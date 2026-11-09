<?php

namespace Tests\Feature;

use App\Models\Setting;
use Tests\FeatureTestCase;

class StorefrontTest extends FeatureTestCase
{
    public function test_home_renders_with_empty_catalogue_and_newsletter_consent(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Feel The')
            ->assertSee('Best Quality.')
            ->assertSee('data-fragrance-scene', false)
            ->assertSee('aria-label="Rotate the fragrance bottle"', false)
            ->assertSee('name="consent"', false)
            ->assertSee('id="mobile-search"', false);
    }

    public function test_catalogue_and_product_purchase_controls_render(): void
    {
        $variant = $this->sellableVariant();
        $variant->product->update(['is_featured' => true]);

        $this->get('/')->assertOk()->assertSee($variant->product->name);
        $this->get('/shop')->assertOk()->assertSee($variant->product->name)
            ->assertSee('product-card');
        $this->get(route('product.show', $variant->product))->assertOk()
            ->assertSee('Add to Cart')->assertSee('Buy Now');
        $this->get('/cart')->assertOk()->assertSee('Your cart is empty.');
        $this->get('/checkout')->assertRedirect(route('cart.index'));
    }

    public function test_home_respects_section_visibility_settings(): void
    {
        Setting::set('home_show_newsletter', false);
        Setting::set('home_show_why_choose', false);
        $this->get('/')->assertOk()->assertDontSee('id="nl-email"', false)
            ->assertDontSee('THE HOUSE OF ARSH');
    }

    public function test_local_media_uses_the_current_storefront_origin(): void
    {
        config(['arsh.media_disk' => 'public']);
        $this->assertSame(asset('storage/products/example.jpg'), media_url('products/example.jpg'));
        $this->assertSame('https://cdn.example.com/photo.jpg', media_url('https://cdn.example.com/photo.jpg'));
    }
}
