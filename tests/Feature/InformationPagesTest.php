<?php

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\PageSeeder;
use Tests\FeatureTestCase;

class InformationPagesTest extends FeatureTestCase
{
    public function test_all_seven_completed_pages_render_when_published(): void
    {
        $this->seed(PageSeeder::class);
        $this->assertSame(7, Page::count());
        foreach (Page::all() as $page) {
            $this->assertStringNotContainsString('OWNER TO CONFIRM', $page->content);
            $this->assertLessThanOrEqual(160, mb_strlen($page->meta_description));
            $page->update(['is_published' => true]);
            $this->get('/'.$page->slug)->assertOk()
                ->assertSee($page->title)
                ->assertSee($page->meta_description)
                ->assertSee('How can we help?');
        }
    }

    public function test_reseeding_preserves_owner_edits_and_publication(): void
    {
        $this->seed(PageSeeder::class);
        $page = Page::where('slug', 'about')->firstOrFail();
        $page->update(['content' => '<p>Our own story.</p>', 'is_published' => true]);
        $this->seed(PageSeeder::class);
        $this->assertSame('<p>Our own story.</p>', $page->fresh()->content);
        $this->assertTrue($page->fresh()->is_published);
    }

    public function test_unpublished_policy_remains_private(): void
    {
        $this->seed(PageSeeder::class);
        $this->get('/privacy-policy')->assertNotFound();
    }
}
