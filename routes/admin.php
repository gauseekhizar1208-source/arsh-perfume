<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;



Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Products (CSV routes are declared before the resource so "export" is not read as a {product} id)
Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
Route::delete('products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
Route::patch('products/{product}/images/{image}/primary', [ProductController::class, 'makePrimaryImage'])->name('products.images.primary');
Route::resource('products', ProductController::class);

// Categories
Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
Route::patch('categories/{category}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');
Route::resource('categories', CategoryController::class)->except(['show']);

// Inventory
Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
Route::post('inventory/{variant}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

// Orders
Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('orders/export', [OrderController::class, 'export'])->name('orders.export');
Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
Route::patch('orders/{order}/tracking', [OrderController::class, 'updateTracking'])->name('orders.tracking');
Route::post('orders/{order}/mark-paid', [OrderController::class, 'markPaid'])->name('orders.mark-paid');
Route::post('orders/{order}/verify-upi', [OrderController::class, 'verifyUpi'])->name('orders.verify-upi');
Route::post('orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');

// Customers
Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
Route::patch('customers/{customer}/toggle', [CustomerController::class, 'toggle'])->name('customers.toggle');

// Coupons
Route::resource('coupons', CouponController::class)->except(['show']);

// Content management
Route::resource('banners', BannerController::class)->except(['show']);
Route::get('pages', [PageController::class, 'index'])->name('pages.index');
Route::get('pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
Route::put('pages/{page}', [PageController::class, 'update'])->name('pages.update');
Route::post('pages/{page}/publish', [PageController::class, 'publish'])->name('pages.publish');
Route::post('pages/{page}/unpublish', [PageController::class, 'unpublish'])->name('pages.unpublish');
Route::get('pages/{page}/preview', [PageController::class, 'preview'])->name('pages.preview');
Route::resource('faqs', FaqController::class)->except(['show']);

// Reviews & enquiries
Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::patch('reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
Route::patch('reviews/{review}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');
Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

Route::get('enquiries', [EnquiryController::class, 'index'])->name('enquiries.index');
Route::get('enquiries/{enquiry}', [EnquiryController::class, 'show'])->name('enquiries.show');
Route::patch('enquiries/{enquiry}', [EnquiryController::class, 'update'])->name('enquiries.update');

Route::get('newsletter', [NewsletterController::class, 'index'])->name('newsletter.index');
Route::get('newsletter/export', [NewsletterController::class, 'export'])->name('newsletter.export');

// Settings & reports
Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
Route::get('reports/customers', [ReportController::class, 'customers'])->name('reports.customers');

Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
