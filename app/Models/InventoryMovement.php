<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = [
        'initial' => 'Initial stock',
        'adjustment' => 'Manual adjustment',
        'sale' => 'Sale',
        'cancellation_restore' => 'Restored (cancellation)',
        'return_restore' => 'Restored (return)',
        'correction' => 'Stock take correction',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
