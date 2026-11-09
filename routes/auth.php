<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

// There is deliberately NO route that can create an administrator. See `php artisan arsh:create-admin`.
Route::middleware('guest')->group(function () {
    Route::get('/auth/google/redirect', [GoogleLoginController::class, 'redirect'])->middleware('throttle:oauth')->name('auth.google');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:password-reset')->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:password-reset')->name('password.store');
});

Route::get('/auth/google/callback', [GoogleLoginController::class, 'callback'])->middleware('throttle:oauth')->name('auth.google.callback');
Route::post('/account/google/connect', [GoogleLoginController::class, 'connect'])->middleware(['auth', 'throttle:oauth'])->name('account.google.connect');

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
