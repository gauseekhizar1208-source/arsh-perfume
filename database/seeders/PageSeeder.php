<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Policy and about pages are created as UNPUBLISHED drafts. The text is a neutral template with
 * [OWNER TO CONFIRM] markers. The owner must complete and review each page before publishing it in Admin.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $todo = '<p><em>[OWNER TO CONFIRM: '.'%s'.']</em></p>';

        $pages = [
            'about' => ['About Arsh Perfume', '<p>Arsh Perfume offers attars, roll-on perfumes and fragrance oils. Tagline: Feel the Best Quality.</p>'.sprintf($todo, 'your story, sourcing and founding details')],
            'shipping-policy' => ['Shipping Policy', sprintf($todo, 'shipping areas, timelines, charges and free-shipping threshold')],
            'returns-refunds' => ['Returns & Refunds', sprintf($todo, 'return window, conditions, refund method and timelines')],
            'cancellation-policy' => ['Cancellation Policy', sprintf($todo, 'when orders can be cancelled and how refunds are processed')],
            'privacy-policy' => ['Privacy Policy', sprintf($todo, 'what personal data is collected, why, retention and contact for requests (review with your legal advisor)')],
            'terms' => ['Terms & Conditions', sprintf($todo, 'terms of sale, pricing and governing law (review with your legal advisor)')],
            'payment-information' => ['Payment Information', '<p>Cash on Delivery and online payment via Razorpay are available where enabled. Card details are processed by Razorpay and never stored by Arsh Perfume.</p>'.sprintf($todo, 'accepted methods and any charges')],
        ];

        foreach ($pages as $slug => [$title, $content]) {
            Page::query()->updateOrCreate(
                ['slug' => $slug],
                ['title' => $title, 'content' => $content, 'is_published' => false]
            );
        }
    }
}
