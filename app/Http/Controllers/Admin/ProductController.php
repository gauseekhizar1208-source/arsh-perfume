<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ActivityLogger;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function __construct(protected InventoryService $inventory)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', Product::STATUSES)],
            'category' => ['nullable', 'integer'],
        ]);

        $products = Product::query()->withTrashed(false)
            ->with(['category', 'images', 'variants'])
            ->search($filters['q'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('category_id', $c))
            ->latest('id')
            ->paginate(15)->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::query()->ordered()->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['status' => Product::STATUS_DRAFT]),
            'variants' => collect([new ProductVariant(['is_active' => true, 'stock' => 0, 'low_stock_threshold' => setting('default_low_stock_threshold', 5)])]),
            'categories' => Category::query()->ordered()->get(),
            'missing' => ['Save the product first to see what is still missing.'],
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $product = Product::query()->create($this->productAttributes($data));
            $this->syncVariants($product, $data['variants']);
            $this->storeImages($product, $request);

            return $product;
        });

        ActivityLogger::log('product.created', $product, "Created product {$product->name}");

        return redirect()->route('admin.products.edit', $product)
            ->with('status', 'Product created.')
            ->with('missing', $product->missingFields());
    }

    public function show(Product $product): RedirectResponse
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function edit(Product $product): View
    {
        $product->load(['images', 'variants']);

        return view('admin.products.form', [
            'product' => $product,
            'variants' => $product->variants->isNotEmpty() ? $product->variants : collect([new ProductVariant(['is_active' => true, 'stock' => 0, 'low_stock_threshold' => 5])]),
            'categories' => Category::query()->ordered()->get(),
            'missing' => $product->missingFields(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product) {
            $data = $request->validated();
            $product->update($this->productAttributes($data));
            $this->syncVariants($product, $data['variants']);
            $this->storeImages($product, $request);
        });

        ActivityLogger::log('product.updated', $product, "Updated product {$product->name}");

        return back()->with('status', 'Product saved.')->with('missing', $product->fresh()->missingFields());
    }

    /**
     * Products with order history are archived (kept for invoices and history). Only unsold products are deleted.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $sold = $product->variants()->whereHas('orderItems')->exists() || $product->sales_count > 0;

        if ($sold) {
            $product->update(['status' => Product::STATUS_ARCHIVED]);
            ActivityLogger::log('product.archived', $product, "Archived {$product->name} (has order history)");

            return redirect()->route('admin.products.index')->with('status', 'Product has order history, so it was archived instead of deleted.');
        }

        $product->images()->get()->each(fn ($img) => Storage::disk(config('arsh.media_disk'))->delete($img->path));
        ActivityLogger::log('product.deleted', null, "Deleted product {$product->name}", ['sku' => $product->sku]);
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);
        Storage::disk(config('arsh.media_disk'))->delete($image->path);
        $image->delete();

        return back()->with('status', 'Image removed.');
    }

    public function makePrimaryImage(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);
        DB::transaction(function () use ($product, $image) {
            $product->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return back()->with('status', 'Primary image updated.');
    }

    /** CSV export of the catalogue (one row per variant). */
    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['product_sku', 'name', 'slug', 'status', 'category', 'fragrance_family', 'variant_sku', 'variant', 'price', 'sale_price', 'stock']);
            Product::query()->withTrashed(false)->with(['category', 'variants'])->orderBy('id')->chunk(200, function ($products) use ($out) {
                foreach ($products as $p) {
                    foreach ($p->variants as $v) {
                        fputcsv($out, [$p->sku, $p->name, $p->slug, $p->status, $p->category?->name, $p->fragrance_family, $v->sku, $v->name, $v->price, $v->sale_price, $v->stock]);
                    }
                }
            });
            fclose($out);
        }, 'arsh-products-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    protected function productAttributes(array $data): array
    {
        $attributes = collect($data)->except(['variants', 'images'])->all();
        $attributes['slug'] = $attributes['slug'] ?? Str::slug($attributes['name']);
        $attributes['is_featured'] = (bool) ($attributes['is_featured'] ?? false);
        $attributes['is_bestseller'] = (bool) ($attributes['is_bestseller'] ?? false);
        $attributes['specifications'] = array_values(array_filter($attributes['specifications'] ?? [], fn ($s) => filled($s['label'] ?? null)));

        return $attributes;
    }

    /** Keep only the submitted variant rows: update existing ones, create new ones, delete removed ones without history. */
    protected function syncVariants(Product $product, array $rows): void
    {
        $keep = [];
        foreach (array_values($rows) as $index => $row) {
            $variant = isset($row['id']) ? $product->variants()->whereKey($row['id'])->first() : null;
            $variant ??= new ProductVariant;
            $variant->product_id = $product->id;
            $variant->fill([
                'name' => $row['name'],
                'size_ml' => $row['size_ml'] ?? null,
                'sku' => $row['sku'],
                'price' => filled($row['price'] ?? null) ? $row['price'] : null,
                'sale_price' => filled($row['sale_price'] ?? null) ? $row['sale_price'] : null,
                'low_stock_threshold' => (int) ($row['low_stock_threshold'] ?? setting('default_low_stock_threshold', 5)),
                'is_active' => (bool) ($row['is_active'] ?? true),
                'sort_order' => $index,
            ]);
            $variant->save();

            // Stock is changed ONLY through InventoryService (locked, logged). Initial stock is set on creation.
            if (! $variant->wasRecentlyCreated) {
                $this->inventory->setStock($variant, (int) $row['stock'], 'adjustment', 'Set from product form', auth()->user());
            } else {
                $this->inventory->setStock($variant, (int) $row['stock'], 'initial', 'Initial stock', auth()->user());
            }
            $keep[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keep)->get()->each(function (ProductVariant $v) {
            if ($v->orderItems()->exists()) {
                $v->update(['is_active' => false]);   // history stays intact: deactivate instead of delete
            } else {
                $v->delete();
            }
        });
    }

    protected function storeImages(Product $product, Request $request): void
    {
        $files = $request->file('images', []);
        if (! $files) {
            return;
        }

        $disk = config('arsh.media_disk');
        $position = (int) $product->images()->max('sort_order') + 1;
        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        foreach ($files as $file) {
            // Random name, extension taken from the verified mime type: user-supplied names are never trusted.
            $ext = $file->guessExtension() ?: 'jpg';
            $path = $file->storeAs("products/{$product->id}", Str::random(32).'.'.$ext, $disk);

            $product->images()->create([
                'path' => $path,
                'alt' => $product->name,
                'sort_order' => $position++,
                'is_primary' => ! $hasPrimary,
            ]);
            $hasPrimary = true;
        }
    }
}
