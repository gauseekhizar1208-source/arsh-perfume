<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_ACTIVE, self::STATUS_ARCHIVED];

    protected $fillable = [
        'category_id', 'sku', 'name', 'slug', 'short_description', 'description', 'fragrance_family',
        'top_notes', 'heart_notes', 'base_notes', 'longevity', 'projection', 'specifications',
        'is_featured', 'is_bestseller', 'status', 'meta_title', 'meta_description', 'owner_notes',
    ];

    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'is_featured' => 'boolean',
            'is_bestseller' => 'boolean',
            'sales_count' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ---- relations -------------------------------------------------------
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved')->latest();
    }

    // ---- scopes ----------------------------------------------------------
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeBestsellers($query)
    {
        return $query->where('is_bestseller', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        return $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('sku', 'like', $like));
    }

    /** Storefront listing: only active products that can actually be bought (at least one priced, active variant). */
    public function scopeVisibleInShop($query)
    {
        return $query->active()->whereHas('variants', fn ($q) => $q->purchasable());
    }

    // ---- display helpers (expect `variants` and `images` to be eager loaded in lists) ----
    public function purchasableVariants()
    {
        return $this->variants->filter->is_purchasable->values();
    }

    public function getPrimaryImageUrlAttribute(): string
    {
        $image = $this->images->first();

        return media_url($image?->path);
    }

    public function getHasImageAttribute(): bool
    {
        return $this->images->isNotEmpty();
    }

    /** Lowest price a shopper would pay (decimal string) or null if nothing is priced yet. */
    public function getStartingPriceAttribute(): ?string
    {
        $prices = $this->purchasableVariants()->map(fn ($v) => Money::toPaise($v->effective_price));

        return $prices->isEmpty() ? null : Money::fromPaise((int) $prices->min());
    }

    /** List price of the same variant that gives the starting price (to show a struck-through price). */
    public function getStartingListPriceAttribute(): ?string
    {
        $variant = $this->purchasableVariants()->sortBy(fn ($v) => Money::toPaise($v->effective_price))->first();

        return $variant && $variant->is_on_sale ? $variant->price : null;
    }

    public function getInStockAttribute(): bool
    {
        return $this->purchasableVariants()->contains(fn ($v) => $v->stock > 0);
    }

    public function getAvailabilityLabelAttribute(): string
    {
        return $this->in_stock ? 'In stock' : 'Out of stock';
    }

    public function getAverageRatingAttribute(): ?float
    {
        $approved = $this->relationLoaded('approvedReviews') ? $this->approvedReviews : $this->approvedReviews()->get();

        return $approved->isEmpty() ? null : round($approved->avg('rating'), 1);
    }

    /**
     * What the owner still has to fill in before this product may be set to Active.
     * Returns a list of human readable strings; empty = ready to publish.
     */
    public function missingFields(): array
    {
        $missing = [];
        $variants = $this->exists ? $this->variants()->get() : collect();

        if (blank($this->name)) {
            $missing[] = 'name';
        }
        if (blank(strip_tags((string) $this->description))) {
            $missing[] = 'description';
        }
        if (! $this->exists || $this->images()->count() === 0) {
            $missing[] = 'at least one product image';
        }
        if (blank($this->category_id)) {
            $missing[] = 'category';
        }
        if (! $variants->contains(fn ($v) => $v->is_active && $v->price !== null && (float) $v->price > 0)) {
            $missing[] = 'at least one active size/variant with a price';
        }

        return $missing;
    }

    public function isReadyToPublish(): bool
    {
        return $this->missingFields() === [];
    }
}
