<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\TrackOrderController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront routes. Admin routes live in routes/admin.php (prefix /admin, middleware web+auth+admin).
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// Catalogue
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
Route::get('/collections/{category:slug}', [CollectionController::class, 'show'])->name('collections.show');
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('product.show');
Route::post('/product/{product:slug}/reviews', [ReviewController::class, 'store'])
    ->middleware(['auth', 'throttle:reviews'])->name('reviews.store');

// Cart
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::post('/cart/buy-now', [CartController::class, 'buyNow'])->name('cart.buy-now');
Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->middleware('throttle:coupon')->name('cart.coupon.apply');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

// Checkout & payment
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::get('/checkout/upi/{order:order_number}', [\App\Http\Controllers\UpiPaymentController::class, 'show'])->name('checkout.upi');
Route::post('/checkout/upi/{order:order_number}/reference', [\App\Http\Controllers\UpiPaymentController::class, 'submitReference'])->middleware('throttle:checkout')->name('checkout.upi.reference');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
Route::get('/checkout/pay/{order:order_number}', [PaymentController::class, 'show'])->name('checkout.pay');
Route::post('/checkout/pay/{order:order_number}/verify', [PaymentController::class, 'verify'])->middleware('throttle:checkout')->name('checkout.verify');
Route::post('/checkout/pay/{order:order_number}/failed', [PaymentController::class, 'failed'])->middleware('throttle:checkout')->name('checkout.failed');
Route::post('/checkout/pay/{order:order_number}/simulate', [PaymentController::class, 'simulate'])->middleware('throttle:checkout')->name('checkout.simulate');
Route::get('/order-success', [OrderController::class, 'success'])->name('order.success');

// Payment gateway webhook (CSRF-exempt; authenticated by HMAC signature, idempotent by event id)
Route::post('/webhooks/razorpay', [PaymentWebhookController::class, 'handle'])->name('webhooks.razorpay');

// Order tracking (guests: order number + email; signed-in customers: their own orders)
Route::get('/track-order', [TrackOrderController::class, 'index'])->name('track-order');
Route::post('/track-order', [TrackOrderController::class, 'lookup'])->middleware('throttle:track')->name('track-order.lookup');

// Content pages (CMS-driven; unpublished pages are 404 for the public)
Route::get('/about', [PageController::class, 'show'])->defaults('slug', 'about')->name('pages.about');
Route::get('/shipping-policy', [PageController::class, 'show'])->defaults('slug', 'shipping-policy')->name('pages.shipping');
Route::get('/returns-refunds', [PageController::class, 'show'])->defaults('slug', 'returns-refunds')->name('pages.returns');
Route::get('/privacy-policy', [PageController::class, 'show'])->defaults('slug', 'privacy-policy')->name('pages.privacy');
Route::get('/terms', [PageController::class, 'show'])->defaults('slug', 'terms')->name('pages.terms');
Route::get('/cancellation-policy', [PageController::class, 'show'])->defaults('slug', 'cancellation-policy')->name('pages.cancellation');
Route::get('/payment-information', [PageController::class, 'show'])->defaults('slug', 'payment-information')->name('pages.payment');
Route::get('/faq', [PageController::class, 'faq'])->name('pages.faq');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('throttle:newsletter')->name('newsletter.store');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// SEO
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Wishlist & account (signed-in customers)
Route::middleware('auth')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product:slug}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

    Route::get('/account', [AccountController::class, 'index'])->name('account');
    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');

    Route::post('/account/addresses', [AddressController::class, 'store'])->name('account.addresses.store');
    Route::put('/account/addresses/{address}', [AddressController::class, 'update'])->name('account.addresses.update');
    Route::delete('/account/addresses/{address}', [AddressController::class, 'destroy'])->name('account.addresses.destroy');

    Route::get('/account/orders', [OrderController::class, 'index'])->name('account.orders.index');
    Route::get('/account/orders/{order:order_number}', [OrderController::class, 'show'])->name('account.orders.show');
    Route::post('/account/orders/{order:order_number}/cancel', [OrderController::class, 'cancel'])->name('account.orders.cancel');
});

require __DIR__.'/auth.php';
