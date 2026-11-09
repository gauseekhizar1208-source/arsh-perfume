<?php

use App\Models\Setting;
use App\Support\Money;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    /**
     * Read a runtime setting (database value, then config/arsh.php default, then $default).
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('money')) {
    /**
     * Format a decimal string / number as rupees using Indian digit grouping: 123456.5 => ₹1,23,456.50
     * Whole amounts drop the decimals: 109 => ₹109.
     */
    function money(string|int|float|null $amount): string
    {
        return Money::format($amount);
    }
}

if (! function_exists('media_url')) {
    /**
     * Public URL for a stored image path, or the placeholder when no path is set.
     */
    function media_url(?string $path, ?string $fallback = null): string
    {
        if (blank($path)) {
            return $fallback ?? asset('images/placeholder.svg');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        // Local media follows the current storefront host, including preview ports.
        if (config('arsh.media_disk') === 'public') {
            return asset('storage/'.ltrim($path, '/'));
        }

        return Storage::disk(config('arsh.media_disk'))->url($path);
    }
}

if (! function_exists('order_status_label')) {
    function order_status_label(string $status): string
    {
        return config("arsh.order_statuses.$status", ucfirst(str_replace('_', ' ', $status)));
    }
}
