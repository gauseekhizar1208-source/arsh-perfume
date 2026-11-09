<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['consent_given_at' => 'datetime', 'unsubscribed_at' => 'datetime'];
    }

    public function scopeActive($query)
    {
        return $query->whereNull('unsubscribed_at');
    }
}
