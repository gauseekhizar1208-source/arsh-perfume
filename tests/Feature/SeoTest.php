<?php

namespace Tests\Feature;

use App\Models\Faq;
use Tests\FeatureTestCase;

class SeoTest extends FeatureTestCase
{
    public function test_home_has_social_meta_and_organization_schema(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large">', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false)
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"@type":"WebSite"', false);
    }

    public function test_product_and_collection_have_breadcrumbs(): void
    {
        $product = $this->sellableVariant()->product;

        $this->get(route('product.show', $product))->assertOk()
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('og:type" content="product"', false);

        if ($product->category) {
            $this->get(route('collections.show', $product->category))->assertOk()
                ->assertSee('"@type":"BreadcrumbList"', false);
        }
    }

    public function test_private_pages_are_noindex(): void
    {
        foreach (['/cart', '/login', '/register', '/track-order'] as $url) {
            $this->get($url)->assertOk()->assertSee('content="noindex, nofollow"', false);
        }
        $this->get('/shop')->assertOk()->assertDontSee('noindex', false);
    }

    public function test_faq_page_has_faq_schema(): void
    {
        Faq::query()->create(['question' => 'How long does attar last?', 'answer' => 'Many hours.', 'is_active' => true, 'sort_order' => 1]);

        $this->get('/faq')->assertOk()->assertSee('"@type":"FAQPage"', false)
            ->assertSee('How long does attar last?', false);
    }

    public function test_robots_and_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:');
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml');
    }
}
