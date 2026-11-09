<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Admin panel: only authenticated, active users holding the admin role get in.
            Route::middleware(['web', 'auth', 'admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        // Guests hitting any auth-protected page (account, admin, …) are sent to the login
        // screen instead of getting an error. API/JSON requests still receive a 401.
        $middleware->redirectGuestsTo(fn () => route('login'));

        // The Razorpay webhook is authenticated by its HMAC signature instead of a CSRF token.
        $middleware->validateCsrfTokens(except: ['webhooks/razorpay']);

        // Disabled accounts are signed out on their next request.
        $middleware->web(append: [EnsureUserIsActive::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Custom error pages live in resources/views/errors (404, 403, 419, 429, 500, 503).
    })
    ->create();
