<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    // Publishing fields are set only by the admin PageController (never from a public request).
    protected $fillable = ['title', 'content', 'meta_title', 'meta_description', 'is_published', 'reviewed_at', 'reviewed_by'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'reviewed_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
