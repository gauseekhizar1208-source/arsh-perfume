# HTTP route reference

[Documentation home](DOCUMENTATION.md)

Generated from the registered Laravel route collection on 2026-10-27.

These are session-based web routes, not a separately versioned public REST API. Mutating browser forms normally require a CSRF token. PATCH, PUT and DELETE forms use Laravel method spoofing. The Razorpay webhook is the explicit CSRF exception and verifies its signature in the controller.

The middleware column shows route aliases. The `web` group also includes active-account checks; policies and controller-level order/session ownership checks are additional to this column. A registered route is not proof that its full behavior has been tested. Parameters such as `{product}` use the binding configured in source; storefront products/categories use slugs and storefront orders use order numbers.

Total routes: **140**. Missing controller actions: **1**.

Regenerate from the project root: `php docs/tools/generate-reference.php` (requires installed Composer dependencies; writes documentation only).

## Storefront and checkout

| Method | URL | Route name | Controller action | Middleware | Implementation |
|---|---|---|---|---|---|
| `GET&#124;HEAD` | `/` | home | `HomeController@index` | web | Registered |
| `GET&#124;HEAD` | `/shop` | shop | `ShopController@index` | web | Registered |
| `GET&#124;HEAD` | `/collections` | collections.index | `CollectionController@index` | web | Registered |
| `GET&#124;HEAD` | `/collections/{category}` | collections.show | `CollectionController@show` | web | Registered |
| `GET&#124;HEAD` | `/product/{product}` | product.show | `ProductController@show` | web | Registered |
| `POST` | `/product/{product}/reviews` | reviews.store | `ReviewController@store` | web, auth, throttle:reviews | Registered |
| `GET&#124;HEAD` | `/cart` | cart.index | `CartController@index` | web | Registered |
| `POST` | `/cart/items` | cart.items.store | `CartController@store` | web | Registered |
| `POST` | `/cart/buy-now` | cart.buy-now | `CartController@buyNow` | web | Registered |
| `PATCH` | `/cart/items/{item}` | cart.items.update | `CartController@update` | web | Registered |
| `DELETE` | `/cart/items/{item}` | cart.items.destroy | `CartController@destroy` | web | Registered |
| `POST` | `/cart/coupon` | cart.coupon.apply | `CartController@applyCoupon` | web, throttle:coupon | Registered |
| `DELETE` | `/cart/coupon` | cart.coupon.remove | `CartController@removeCoupon` | web | Registered |
| `GET&#124;HEAD` | `/checkout` | checkout.show | `CheckoutController@show` | web | Registered |
| `GET&#124;HEAD` | `/checkout/upi/{order}` | checkout.upi | `UpiPaymentController@show` | web | Registered |
| `POST` | `/checkout/upi/{order}/reference` | checkout.upi.reference | `UpiPaymentController@submitReference` | web, throttle:checkout | Registered |
| `POST` | `/checkout` | checkout.store | `CheckoutController@store` | web, throttle:checkout | Registered |
| `GET&#124;HEAD` | `/checkout/pay/{order}` | checkout.pay | `PaymentController@show` | web | Registered |
| `POST` | `/checkout/pay/{order}/verify` | checkout.verify | `PaymentController@verify` | web, throttle:checkout | Registered |
| `POST` | `/checkout/pay/{order}/failed` | checkout.failed | `PaymentController@failed` | web, throttle:checkout | Registered |
| `POST` | `/checkout/pay/{order}/simulate` | checkout.simulate | `PaymentController@simulate` | web, throttle:checkout | Registered |
| `GET&#124;HEAD` | `/order-success` | order.success | `OrderController@success` | web | Registered |
| `POST` | `/webhooks/razorpay` | webhooks.razorpay | `PaymentWebhookController@handle` | web | Registered |
| `GET&#124;HEAD` | `/track-order` | track-order | `TrackOrderController@index` | web | Registered |
| `POST` | `/track-order` | track-order.lookup | `TrackOrderController@lookup` | web, throttle:track | Registered |
| `GET&#124;HEAD` | `/about` | pages.about | `PageController@show` | web | Registered |
| `GET&#124;HEAD` | `/shipping-policy` | pages.shipping | `PageController@show` | web | Registered |
| `GET&#124;HEAD` | `/returns-refunds` | pages.returns | `PageController@show` | web | Registered |
| `GET&#124;HEAD` | `/privacy-policy` | pages.privacy | `PageController@show` | web | Registered |
| `GET&#124;HEAD` | `/terms` | pages.terms | `PageController@show` | web | Registered |
| `GET&#124;HEAD` | `/cancellation-policy` | pages.cancellation | `PageController@show` | web | Registered |
| `GET&#124;HEAD` | `/payment-information` | pages.payment | `PageController@show` | web | Registered |
| `GET&#124;HEAD` | `/faq` | pages.faq | `PageController@faq` | web | Registered |
| `GET&#124;HEAD` | `/contact` | contact | `ContactController@show` | web | Registered |
| `POST` | `/contact` | contact.store | `ContactController@store` | web, throttle:contact | Registered |
| `POST` | `/newsletter` | newsletter.store | `NewsletterController@store` | web, throttle:newsletter | Registered |
| `GET&#124;HEAD` | `/newsletter/unsubscribe/{token}` | newsletter.unsubscribe | `NewsletterController@unsubscribe` | web | Registered |
| `GET&#124;HEAD` | `/sitemap.xml` | sitemap | `SeoController@sitemap` | web | Registered |
| `GET&#124;HEAD` | `/robots.txt` | robots | `SeoController@robots` | web | Registered |

## Authentication

| Method | URL | Route name | Controller action | Middleware | Implementation |
|---|---|---|---|---|---|
| `GET&#124;HEAD` | `/auth/google/redirect` | auth.google | `Auth\GoogleLoginController@redirect` | web, guest, throttle:oauth | Registered |
| `GET&#124;HEAD` | `/register` | register | `Auth\RegisterController@create` | web, guest | Registered |
| `POST` | `/register` | (unnamed) | `Auth\RegisterController@store` | web, guest, throttle:register | Registered |
| `GET&#124;HEAD` | `/login` | login | `Auth\LoginController@create` | web, guest | Registered |
| `POST` | `/login` | (unnamed) | `Auth\LoginController@store` | web, guest, throttle:login | Registered |
| `GET&#124;HEAD` | `/forgot-password` | password.request | `Auth\ForgotPasswordController@create` | web, guest | Registered |
| `POST` | `/forgot-password` | password.email | `Auth\ForgotPasswordController@store` | web, guest, throttle:password-reset | Registered |
| `GET&#124;HEAD` | `/reset-password/{token}` | password.reset | `Auth\ResetPasswordController@create` | web, guest | Registered |
| `POST` | `/reset-password` | password.store | `Auth\ResetPasswordController@store` | web, guest, throttle:password-reset | Registered |
| `GET&#124;HEAD` | `/auth/google/callback` | auth.google.callback | `Auth\GoogleLoginController@callback` | web, throttle:oauth | Registered |
| `POST` | `/account/google/connect` | account.google.connect | `Auth\GoogleLoginController@connect` | web, auth, throttle:oauth | Registered |
| `POST` | `/logout` | logout | `Auth\LoginController@destroy` | web, auth | Registered |

## Customer account

| Method | URL | Route name | Controller action | Middleware | Implementation |
|---|---|---|---|---|---|
| `GET&#124;HEAD` | `/wishlist` | wishlist.index | `WishlistController@index` | web, auth | Registered |
| `POST` | `/wishlist/{product}` | wishlist.toggle | `WishlistController@toggle` | web, auth | Registered |
| `GET&#124;HEAD` | `/account` | account | `AccountController@index` | web, auth | Registered |
| `PATCH` | `/account/profile` | account.profile.update | `AccountController@updateProfile` | web, auth | Registered |
| `PUT` | `/account/password` | account.password.update | `AccountController@updatePassword` | web, auth | Registered |
| `POST` | `/account/addresses` | account.addresses.store | `AddressController@store` | web, auth | Registered |
| `PUT` | `/account/addresses/{address}` | account.addresses.update | `AddressController@update` | web, auth | Registered |
| `DELETE` | `/account/addresses/{address}` | account.addresses.destroy | `AddressController@destroy` | web, auth | Registered |
| `GET&#124;HEAD` | `/account/orders` | account.orders.index | `OrderController@index` | web, auth | Registered |
| `GET&#124;HEAD` | `/account/orders/{order}` | account.orders.show | `OrderController@show` | web, auth | Registered |
| `POST` | `/account/orders/{order}/cancel` | account.orders.cancel | `OrderController@cancel` | web, auth | Registered |

## Administration

| Method | URL | Route name | Controller action | Middleware | Implementation |
|---|---|---|---|---|---|
| `GET&#124;HEAD` | `/admin` | admin.dashboard | `Admin\DashboardController@index` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/products/export` | admin.products.export | `Admin\ProductController@export` | web, auth, admin | Registered |
| `POST` | `/admin/products/import` | admin.products.import | `Admin\ProductController@import` | web, auth, admin | **Missing controller method** |
| `DELETE` | `/admin/products/{product}/images/{image}` | admin.products.images.destroy | `Admin\ProductController@destroyImage` | web, auth, admin | Registered |
| `PATCH` | `/admin/products/{product}/images/{image}/primary` | admin.products.images.primary | `Admin\ProductController@makePrimaryImage` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/products` | admin.products.index | `Admin\ProductController@index` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/products/create` | admin.products.create | `Admin\ProductController@create` | web, auth, admin | Registered |
| `POST` | `/admin/products` | admin.products.store | `Admin\ProductController@store` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/products/{product}` | admin.products.show | `Admin\ProductController@show` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/products/{product}/edit` | admin.products.edit | `Admin\ProductController@edit` | web, auth, admin | Registered |
| `PUT&#124;PATCH` | `/admin/products/{product}` | admin.products.update | `Admin\ProductController@update` | web, auth, admin | Registered |
| `DELETE` | `/admin/products/{product}` | admin.products.destroy | `Admin\ProductController@destroy` | web, auth, admin | Registered |
| `POST` | `/admin/categories/reorder` | admin.categories.reorder | `Admin\CategoryController@reorder` | web, auth, admin | Registered |
| `PATCH` | `/admin/categories/{category}/toggle` | admin.categories.toggle | `Admin\CategoryController@toggle` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/categories` | admin.categories.index | `Admin\CategoryController@index` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/categories/create` | admin.categories.create | `Admin\CategoryController@create` | web, auth, admin | Registered |
| `POST` | `/admin/categories` | admin.categories.store | `Admin\CategoryController@store` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/categories/{category}/edit` | admin.categories.edit | `Admin\CategoryController@edit` | web, auth, admin | Registered |
| `PUT&#124;PATCH` | `/admin/categories/{category}` | admin.categories.update | `Admin\CategoryController@update` | web, auth, admin | Registered |
| `DELETE` | `/admin/categories/{category}` | admin.categories.destroy | `Admin\CategoryController@destroy` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/inventory` | admin.inventory.index | `Admin\InventoryController@index` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/inventory/movements` | admin.inventory.movements | `Admin\InventoryController@movements` | web, auth, admin | Registered |
| `POST` | `/admin/inventory/{variant}/adjust` | admin.inventory.adjust | `Admin\InventoryController@adjust` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/orders` | admin.orders.index | `Admin\OrderController@index` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/orders/export` | admin.orders.export | `Admin\OrderController@export` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/orders/{order}` | admin.orders.show | `Admin\OrderController@show` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/orders/{order}/invoice` | admin.orders.invoice | `Admin\OrderController@invoice` | web, auth, admin | Registered |
| `PATCH` | `/admin/orders/{order}/status` | admin.orders.status | `Admin\OrderController@updateStatus` | web, auth, admin | Registered |
| `PATCH` | `/admin/orders/{order}/tracking` | admin.orders.tracking | `Admin\OrderController@updateTracking` | web, auth, admin | Registered |
| `POST` | `/admin/orders/{order}/mark-paid` | admin.orders.mark-paid | `Admin\OrderController@markPaid` | web, auth, admin | Registered |
| `POST` | `/admin/orders/{order}/verify-upi` | admin.orders.verify-upi | `Admin\OrderController@verifyUpi` | web, auth, admin | Registered |
| `POST` | `/admin/orders/{order}/refund` | admin.orders.refund | `Admin\OrderController@refund` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/customers` | admin.customers.index | `Admin\CustomerController@index` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/customers/{customer}` | admin.customers.show | `Admin\CustomerController@show` | web, auth, admin | Registered |
| `PATCH` | `/admin/customers/{customer}/toggle` | admin.customers.toggle | `Admin\CustomerController@toggle` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/coupons` | admin.coupons.index | `Admin\CouponController@index` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/coupons/create` | admin.coupons.create | `Admin\CouponController@create` | web, auth, admin | Registered |
| `POST` | `/admin/coupons` | admin.coupons.store | `Admin\CouponController@store` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/coupons/{coupon}/edit` | admin.coupons.edit | `Admin\CouponController@edit` | web, auth, admin | Registered |
| `PUT&#124;PATCH` | `/admin/coupons/{coupon}` | admin.coupons.update | `Admin\CouponController@update` | web, auth, admin | Registered |
| `DELETE` | `/admin/coupons/{coupon}` | admin.coupons.destroy | `Admin\CouponController@destroy` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/banners` | admin.banners.index | `Admin\BannerController@index` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/banners/create` | admin.banners.create | `Admin\BannerController@create` | web, auth, admin | Registered |
| `POST` | `/admin/banners` | admin.banners.store | `Admin\BannerController@store` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/banners/{banner}/edit` | admin.banners.edit | `Admin\BannerController@edit` | web, auth, admin | Registered |
| `PUT&#124;PATCH` | `/admin/banners/{banner}` | admin.banners.update | `Admin\BannerController@update` | web, auth, admin | Registered |
| `DELETE` | `/admin/banners/{banner}` | admin.banners.destroy | `Admin\BannerController@destroy` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/pages` | admin.pages.index | `Admin\PageController@index` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/pages/{page}/edit` | admin.pages.edit | `Admin\PageController@edit` | web, auth, admin | Registered |
| `PUT` | `/admin/pages/{page}` | admin.pages.update | `Admin\PageController@update` | web, auth, admin | Registered |
| `POST` | `/admin/pages/{page}/publish` | admin.pages.publish | `Admin\PageController@publish` | web, auth, admin | Registered |
| `POST` | `/admin/pages/{page}/unpublish` | admin.pages.unpublish | `Admin\PageController@unpublish` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/pages/{page}/preview` | admin.pages.preview | `Admin\PageController@preview` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/faqs` | admin.faqs.index | `Admin\FaqController@index` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/faqs/create` | admin.faqs.create | `Admin\FaqController@create` | web, auth, admin | Registered |
| `POST` | `/admin/faqs` | admin.faqs.store | `Admin\FaqController@store` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/faqs/{faq}/edit` | admin.faqs.edit | `Admin\FaqController@edit` | web, auth, admin | Registered |
| `PUT&#124;PATCH` | `/admin/faqs/{faq}` | admin.faqs.update | `Admin\FaqController@update` | web, auth, admin | Registered |
| `DELETE` | `/admin/faqs/{faq}` | admin.faqs.destroy | `Admin\FaqController@destroy` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/reviews` | admin.reviews.index | `Admin\ReviewController@index` | web, auth, admin | Registered |
| `PATCH` | `/admin/reviews/{review}/approve` | admin.reviews.approve | `Admin\ReviewController@approve` | web, auth, admin | Registered |
| `PATCH` | `/admin/reviews/{review}/reject` | admin.reviews.reject | `Admin\ReviewController@reject` | web, auth, admin | Registered |
| `DELETE` | `/admin/reviews/{review}` | admin.reviews.destroy | `Admin\ReviewController@destroy` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/enquiries` | admin.enquiries.index | `Admin\EnquiryController@index` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/enquiries/{enquiry}` | admin.enquiries.show | `Admin\EnquiryController@show` | web, auth, admin | Registered |
| `PATCH` | `/admin/enquiries/{enquiry}` | admin.enquiries.update | `Admin\EnquiryController@update` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/newsletter` | admin.newsletter.index | `Admin\NewsletterController@index` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/newsletter/export` | admin.newsletter.export | `Admin\NewsletterController@export` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/settings` | admin.settings.edit | `Admin\SettingController@edit` | web, auth, admin | Registered |
| `PUT` | `/admin/settings` | admin.settings.update | `Admin\SettingController@update` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/reports` | admin.reports.index | `Admin\ReportController@index` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/reports/sales` | admin.reports.sales | `Admin\ReportController@sales` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/reports/inventory` | admin.reports.inventory | `Admin\ReportController@inventory` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/reports/customers` | admin.reports.customers | `Admin\ReportController@customers` | web, auth, admin | Registered |
| `GET&#124;HEAD` | `/admin/activity-log` | admin.activity-log.index | `Admin\ActivityLogController@index` | web, auth, admin | Registered |

## Framework endpoints

| Method | URL | Route name | Controller action | Middleware | Implementation |
|---|---|---|---|---|---|
| `GET&#124;HEAD` | `/up` | (unnamed) | `Closure` | (none) | Registered |
| `GET&#124;HEAD` | `/storage/{path}` | storage.local | `Closure` | (none) | Registered |
| `PUT` | `/storage/{path}` | storage.local.upload | `Closure` | (none) | Registered |

