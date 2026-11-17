<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/** Create complete drafts without overwriting existing editorial changes. */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require __DIR__.'/content/pages.php' as $slug => $data) {
            if (Page::query()->where('slug', $slug)->exists()) {
                continue;
            }
            $page = new Page;
            $page->slug = $slug;
            $page->fill([...$data, 'is_published' => false]);
            $page->save();
        }
    }
}
