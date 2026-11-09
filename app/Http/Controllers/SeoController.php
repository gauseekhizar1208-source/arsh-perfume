<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Page;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect(['home', 'shop', 'collections.index', 'pages.faq', 'contact'])
            ->map(fn ($name) => ['loc' => route($name), 'lastmod' => now()->toDateString()]);

        $urls = $urls->merge(
            Category::query()->active()->get()->map(fn ($c) => ['loc' => route('collections.show', $c), 'lastmod' => $c->updated_at->toDateString()])
        )->merge(
            Product::query()->visibleInShop()->get()->map(fn ($p) => ['loc' => route('product.show', $p), 'lastmod' => $p->updated_at->toDateString()])
        )->merge(
            Page::query()->published()->get()->map(fn ($p) => ['loc' => url('/'.$p->slug), 'lastmod' => $p->updated_at->toDateString()])
        );

        $xml = view('seo.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $body = "User-agent: *\nDisallow: /admin\nDisallow: /account\nDisallow: /cart\nDisallow: /checkout\nDisallow: /webhooks\n\nSitemap: ".route('sitemap')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }
}
