<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The seven products the owner named. Only confirmed facts are entered:
 *  - name, size and price where the owner gave them,
 *  - stock is 0 (no stock count was supplied),
 *  - no descriptions, notes, images or reviews are invented.
 * Every product is seeded as a DRAFT so it cannot be sold until the owner completes it.
 * Products without a confirmed price keep their size row unpriced, so they cannot be bought.
 */
class ProductSeeder extends Seeder
{
    /** [name, size_ml, price, notes-for-owner] */
    public const PRODUCTS = [
        ['Ikhlas Roll-On Attar', 8, '109.00', null],
        ['Green Apple Roll-On Attar', 8, '99.00', null],
        ['Aswad Roll-On Attar', 8, '119.00', null],
        ['Broot Roll-On Attar', 8, '99.00', null],
        ['Arya Roll-On Attar', null, null, 'Price and size not yet confirmed.'],
        ['Tasleem Roll-On Attar', null, null, 'Price and size not yet confirmed.'],
        ['Labbek Roll-On Attar', null, null, 'Price and size not yet confirmed.'],
    ];

    public function run(): void
    {
        $category = Category::query()->where('slug', Str::slug('Roll-On Attar'))->first();

        foreach (self::PRODUCTS as [$name, $sizeMl, $price, $ownerNote]) {
            $slug = Str::slug($name);
            $sku = 'ARSH-'.strtoupper(Str::before($slug, '-roll-on')).'-'.($sizeMl ?? 'TBC');

            $product = Product::withTrashed()->updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $category?->id,
                    'sku' => $sku,
                    'name' => $name,
                    'status' => Product::STATUS_DRAFT,
                    'owner_notes' => $this->missingNotes($name, $sizeMl, $price, $ownerNote),
                ]
            );

            $product->variants()->updateOrCreate(
                ['sku' => $sku.'-V1'],
                [
                    'name' => $sizeMl ? "{$sizeMl} ml" : 'Size to be confirmed',
                    'size_ml' => $sizeMl,
                    'price' => $price,
                    'sale_price' => null,
                    'is_active' => true,
                    'sort_order' => 0,
                    'low_stock_threshold' => (int) setting('default_low_stock_threshold', 5),
                ]
            );
        }
    }

    protected function missingNotes(string $name, ?int $sizeMl, ?string $price, ?string $ownerNote): string
    {
        $missing = ['product photos', 'description', 'fragrance notes', 'opening stock count', 'longevity and projection (only if tested)'];
        if ($price === null) {
            $missing[] = 'confirmed price and size';
        }

        return "{$name}: still missing before publishing - ".implode(', ', $missing).'.'.($ownerNote ? " $ownerNote" : '');
    }
}
