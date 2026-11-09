<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'size_ml', 'sku', 'price', 'sale_price', 'stock', 'low_stock_threshold', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_variant_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest('id');
    }

    /** Active and priced: the only variants a customer can add to a cart. */
    public function scopePurchasable($query)
    {
        return $query->where('is_active', true)->whereNotNull('price')->where('price', '>', 0);
    }

    public function getIsPurchasableAttribute(): bool
    {
        return $this->is_active && $this->price !== null && (float) $this->price > 0;
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null
            && (float) $this->sale_price > 0
            && $this->price !== null
            && Money::toPaise($this->sale_price) < Money::toPaise($this->price);
    }

    /** The price a customer pays right now (sale price when valid, otherwise the regular price). */
    public function getEffectivePriceAttribute(): ?string
    {
        if ($this->price === null) {
            return null;
        }

        return $this->is_on_sale ? $this->sale_price : $this->price;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock > 0 && $this->stock <= $this->low_stock_threshold;
    }

    public function getStockLabelAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'Out of stock';
        }

        return $this->is_low_stock ? "Only {$this->stock} left" : 'In stock';
    }
}
