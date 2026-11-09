<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /** Names from the brief. Owners can rename, reorder or deactivate them in the admin panel. */
    public const NAMES = [
        'Roll-On Attar', 'Floral Fragrances', 'Woody Fragrances', 'Musk Fragrances', 'Oud Collection',
        'Fresh and Fruity Fragrances', 'Premium Collection', 'Bestsellers', 'New Arrivals',
    ];

    public function run(): void
    {
        foreach (self::NAMES as $i => $name) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $i, 'is_active' => true]
            );
        }
    }
}
