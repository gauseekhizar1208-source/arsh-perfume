<?php

namespace App\Providers;

use App\Models\Role;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\RazorpayService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            PricingService::class, CouponService::class, InventoryService::class, CartService::class,
            RazorpayService::class, OrderService::class, PaymentService::class,
        ] as $service) {
            $this->app->singleton($service);
        }
    }

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Paginator::defaultView('pagination.arsh');
        Paginator::defaultSimpleView('pagination.arsh');

        Gate::define('access-admin', fn ($user) => $user->is_active && $user->isAdmin());

        $this->configureRateLimiting();

        // Cart badge for the storefront header.
        View::composer('layouts.app', function ($view) {
            $view->with('cartCount', $this->app->make(CartService::class)->count());
        });
    }

    protected function configureRateLimiting(): void
    {
        $byIp = fn (Request $r) => $r->ip();

        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()));
        RateLimiter::for('oauth', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));
        RateLimiter::for('register', fn (Request $r) => Limit::perMinute(5)->by($r->ip()));
        RateLimiter::for('password-reset', fn (Request $r) => Limit::perMinute(5)->by($r->ip()));
        RateLimiter::for('checkout', fn (Request $r) => Limit::perMinute(10)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('coupon', fn (Request $r) => Limit::perMinute(15)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('track', fn (Request $r) => Limit::perMinute(10)->by($byIp($r)));
        RateLimiter::for('contact', fn (Request $r) => Limit::perMinute(5)->by($byIp($r)));
        RateLimiter::for('newsletter', fn (Request $r) => Limit::perMinute(5)->by($byIp($r)));
        RateLimiter::for('reviews', fn (Request $r) => Limit::perMinute(5)->by($r->user()?->id ?: $r->ip()));
    }
}
