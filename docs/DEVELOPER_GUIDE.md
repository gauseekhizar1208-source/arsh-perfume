# ARSH PERFUME — Developer Guide

> Complete technical documentation for the Arsh Perfume e-commerce platform.
> Last updated: 2026-10-27.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Technology Stack](#2-technology-stack)
3. [Directory Structure](#3-directory-structure)
4. [Installation & Setup](#4-installation--setup)
5. [Configuration](#5-configuration)
6. [Architecture](#6-architecture)
7. [Database Schema](#7-database-schema)
8. [Routing](#8-routing)
9. [Services Layer](#9-services-layer)
10. [Models & Relationships](#10-models--relationships)
11. [Authentication & Authorization](#11-authentication--authorization)
12. [Payment Processing](#12-payment-processing)
13. [Inventory Management](#13-inventory-management)
14. [Cart & Checkout Flow](#14-cart--checkout-flow)
15. [Order Lifecycle](#15-order-lifecycle)
16. [Coupon System](#16-coupon-system)
17. [Admin Panel](#17-admin-panel)
18. [CMS & Content](#18-cms--content)
19. [Frontend Architecture](#19-frontend-architecture)
20. [Testing](#20-testing)
21. [Deployment](#21-deployment)
22. [Security Model](#22-security-model)
23. [Scheduled Tasks & Queues](#23-scheduled-tasks--queues)
24. [API Reference: Helper Functions](#24-api-reference-helper-functions)
25. [Known Issues & Future Work](#25-known-issues--future-work)

---

## 1. Project Overview

**Arsh Perfume** is a server-rendered e-commerce platform for an India-based business selling attars, roll-on perfumes, and fragrance oils. It consists of two main interfaces:

- **Customer Storefront** — browsing, cart, checkout, order tracking, wishlist, account management
- **Admin Back Office** — products, inventory, orders, customers, coupons, reports, CMS, settings

The application is a traditional Laravel monolith (not an SPA). All pages are server-rendered with Blade templates. Client-side interactivity is handled with Alpine.js. There is no Livewire or Inertia.

### Key Design Decisions

| Decision | Reasoning |
|---|---|
| Integer paise for money | Avoids floating-point rounding errors across cart, checkout, orders, refunds |
| Services layer over fat controllers | Business rules live in `app/Services/`, controllers are thin |
| Pessimistic locking for stock | `SELECT ... FOR UPDATE` prevents overselling under concurrent load |
| Idempotency keys for orders | Double-click protection at checkout |
| Historical snapshots in order items | Product/price changes don't retroactively alter past orders |
| No public admin registration | Admin accounts created only via CLI command |
| Simulated payment gateway | Full checkout testing without Razorpay credentials |

---

## 2. Technology Stack

### Backend

| Component | Version | Purpose |
|---|---|---|
| PHP | ^8.2 | Runtime |
| Laravel | ^12.0 | Framework |
| MySQL | 8.0 | Primary database |
| Laravel Socialite | ^5.31 | Google OAuth login |
| Laravel Tinker | ^2.10 | REPL for debugging |

### Frontend

| Component | Version | Purpose |
|---|---|---|
| Tailwind CSS | ^4.0.0 | Utility-first styling |
| Alpine.js | ^3.14.9 | Reactive UI components |
| Vite | ^7.0.0 | Asset bundling |
| Chart.js | ^4.4.9 | Admin dashboard charts |
| Framer Motion | ^14.6.0 | Storefront animations (DOM API, no React) |
| qrcode | ^1.5.4 | Browser-side UPI QR code generation |
| Axios | ^1.11.0 | HTTP client |

### Dev Dependencies

| Component | Purpose |
|---|---|
| PHPUnit ^11.5 | Testing framework |
| Faker ^1.23 | Test data generation (en_IN locale) |
| Mockery ^1.6 | Mocking library |
| Collision ^8.6 | Pretty error reporting |

### Required PHP Extensions

`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd` (image handling), `curl`

---

## 3. Directory Structure

```
arsh-perfume/
├── app/
│   ├── Console/Commands/          # Artisan commands (CreateAdminUser, ExpireUnpaidOrders)
│   ├── Events/                    # OrderPlaced event
│   ├── Exceptions/                # CheckoutException, CouponException, GoogleSignInException
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/             # 15 admin controllers (dashboard, products, orders, etc.)
│   │   │   ├── Auth/              # Login, Register, ForgotPassword, ResetPassword, GoogleLogin
│   │   │   └── *.php              # 16 storefront controllers
│   │   ├── Middleware/            # EnsureUserIsAdmin, EnsureUserIsActive
│   │   └── Requests/             # CheckoutRequest, ProductRequest
│   ├── Listeners/                 # SendOrderPlacedNotifications
│   ├── Models/                    # 20 Eloquent models
│   ├── Notifications/             # NewOrderAdmin, OrderPlaced, OrderStatusChanged
│   ├── Policies/                  # OrderPolicy, UserAddressPolicy
│   ├── Providers/                 # AppServiceProvider
│   ├── Services/                  # 10 service classes (core business logic)
│   └── Support/                   # Money class, helpers.php, OrderAccess
├── bootstrap/
│   └── app.php                    # Application bootstrap, routing, middleware
├── config/
│   ├── arsh.php                   # Business config (statuses, transitions, settings defaults)
│   ├── services.php               # Razorpay, Google OAuth credentials
│   └── *.php                      # Standard Laravel config files
├── database/
│   ├── factories/                 # Category, Product, ProductVariant, User factories
│   ├── migrations/                # 12 migration files (chronologically ordered, 2026-01 to 2026-10)
│   ├── mysql/                     # Hand-written SQL schema (reference only; use migrations)
│   └── seeders/                   # Role, Admin, Category, Product, Page, Database seeders
├── design-system/                 # MASTER.md design direction, admin design notes
├── docs/                          # This guide + database/routes/settings references
├── public/
│   ├── build/                     # Vite compiled assets
│   ├── css/                       # Static CSS
│   ├── images/brand/              # Logo, hero imagery
│   └── index.php                  # Entry point
├── resources/
│   ├── css/                       # app.css (Tailwind entry), admin.css, auth.css, storefront.css, upi.css
│   ├── js/                        # app.js, admin.js, storefront-motion.js, upi-qr.js
│   └── views/                     # 72 Blade templates
│       ├── account/               # Customer account pages
│       ├── admin/                 # Admin panel pages (14 subdirectories)
│       ├── auth/                  # Login, register, password reset
│       ├── components/            # Reusable Blade components
│       ├── errors/                # Custom error pages (403, 404, 419, 429, 500, 503)
│       ├── layouts/               # app.blade.php, admin.blade.php, home.blade.php
│       ├── pages/                 # Storefront pages
│       ├── pagination/            # Custom paginator
│       └── seo/                   # Sitemap template
├── routes/
│   ├── web.php                    # Storefront routes
│   ├── auth.php                   # Auth routes (login, register, Google, password reset)
│   ├── admin.php                  # Admin routes (prefix /admin)
│   └── console.php                # Console command scheduling
├── storage/                       # Logs, cache, compiled views, uploaded files
├── tests/                         # 11 feature tests + 1 unit test
├── composer.json
├── package.json
├── vite.config.js
└── phpunit.xml
```

**File counts:** 100 PHP files in `app/`, 72 Blade templates, 12 migrations, 6 seeders, 12 test files.

---

## 4. Installation & Setup

### Prerequisites

- PHP 8.2+ with required extensions
- Composer 2.x
- Node.js 20 LTS+
- MySQL 8.0
- Apache (with `mod_rewrite`) or Nginx

### Steps

```bash
# 1. Install PHP dependencies
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Create database
mysql -u root -p -e "CREATE DATABASE arsh_perfume CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Edit .env with database credentials (DB_USERNAME, DB_PASSWORD)

# 5. Run migrations and seed data
php artisan migrate --seed

# 6. Build frontend assets
npm install
npm run build          # Production build
# npm run dev          # Dev server with HMR

# 7. Create storage symlink for uploaded images
php artisan storage:link

# 8. Start the server
php artisan serve      # http://localhost:8000
```

### Creating the Admin User

There is **no public admin registration route**. Admins are created exclusively via CLI:

```bash
php artisan arsh:create-admin you@example.com --name="Your Name"
# Prompts for a password interactively
```

Alternatively, set `ADMIN_NAME`, `ADMIN_EMAIL`, and `ADMIN_PASSWORD` in `.env` before running `migrate --seed`. **Delete these values from `.env` after first use.**

### XAMPP-Specific Notes

- Place project at `C:\xampp\htdocs\arsh-perfume`
- Set `DocumentRoot` to the `public/` folder in Apache virtual host config
- PHP 8.2+ is required (upgrade XAMPP's bundled PHP if needed)
- Enable extensions: `pdo_mysql`, `fileinfo`, `gd`, `openssl`, `mbstring` in `php.ini`
- If `storage:link` fails, run terminal as administrator or enable developer mode

### Quickstart (Concurrent Dev)

```bash
composer dev
# Runs: php artisan serve + queue:listen + npm run dev (concurrently)
```

---

## 5. Configuration

### Environment Variables (.env)

| Variable | Purpose | Default |
|---|---|---|
| `APP_ENV` | Environment (`local`, `production`) | `production` |
| `APP_DEBUG` | Debug mode (never `true` in production) | `false` |
| `APP_URL` | Base URL | `http://localhost` |
| `APP_KEY` | Encryption key (generated by `key:generate`) | — |
| `DB_*` | MySQL connection details | — |
| `SESSION_SECURE_COOKIE` | Set `true` in production (HTTPS) | `false` |
| `QUEUE_CONNECTION` | Queue driver (`database` recommended) | `database` |
| `MEDIA_DISK` | Image storage disk (`public` or `s3`) | `public` |
| `MAIL_*` | Mail configuration (`log` for dev) | `log` |
| `RAZORPAY_KEY_ID` | Razorpay API key (public, sent to browser) | — |
| `RAZORPAY_KEY_SECRET` | Razorpay API secret (server-side only) | — |
| `RAZORPAY_WEBHOOK_SECRET` | HMAC secret for webhook verification | — |
| `GOOGLE_CLIENT_ID` | Google OAuth client ID | — |
| `GOOGLE_CLIENT_SECRET` | Google OAuth client secret | — |
| `ADMIN_NAME/EMAIL/PASSWORD` | First admin (delete after use) | — |
| `BCRYPT_ROUNDS` | Password hashing rounds | `12` |

### Business Configuration (config/arsh.php)

This file contains structural constants and defaults for the settings table:

- **Order statuses and transitions** — the whitelist of allowed status changes
- **Payment methods** — COD, Razorpay, UPI
- **Customer cancellable statuses** — `pending`, `confirmed`
- **Fragrance families** — used in product filtering
- **Indian states** — for address forms
- **Settings defaults** — business info, social links, commerce, UPI, invoices, notifications, homepage sections

### Runtime Settings (settings table)

Runtime settings live in the `settings` database table and are accessed via the `setting()` helper:

```php
setting('currency_symbol', '₹');       // Read with default fallback
setting('cod_enabled', true);          // Boolean setting
setting('shipping_flat_rate', '0');    // Decimal setting
```

Settings are grouped by category: business, social, commerce, upi_payments, invoice, notifications, homepage. They are managed through the **Admin > Settings** page.

---

## 6. Architecture

### Request Lifecycle

```
Browser Request
    → public/index.php
    → bootstrap/app.php (middleware, routing)
    → Middleware stack:
        web → EnsureUserIsActive → (auth) → (admin)
    → Controller (thin — validates, delegates)
    → Service (business logic, transactions)
    → Model (data access, scopes, relations)
    → Blade View (server-rendered HTML)
    → Response
```

### Layer Responsibilities

| Layer | Responsibility | Example |
|---|---|---|
| **Controller** | HTTP concerns: validate input, call service, return response | `CheckoutController::store()` |
| **Service** | Business rules, transactions, cross-model logic | `OrderService::place()` |
| **Model** | Data access, relationships, scopes, computed attributes | `Product::scopeVisibleInShop()` |
| **Support** | Utilities shared across layers | `Money::toPaise()`, `setting()` |
| **Middleware** | Request-level guards | `EnsureUserIsAdmin`, `EnsureUserIsActive` |
| **Policy** | Authorization (per-record access control) | `OrderPolicy::view()` |

### Key Architectural Patterns

- **Pessimistic locking** — `lockForUpdate()` on variants, coupons, orders, and payments during checkout to prevent race conditions
- **Stable lock ordering** — Variants are locked `ORDER BY id` to avoid deadlocks
- **Idempotency** — Order creation uses idempotency keys; webhook processing uses unique event IDs
- **Historical snapshots** — Order items snapshot product name, price, variant, SKU, and image at purchase time
- **Integer arithmetic** — All money calculations use integer paise via the `Money` class
- **Event-driven** — `OrderPlaced` event triggers notifications asynchronously

---

## 7. Database Schema

29 application tables + Laravel support tables. The schema is MySQL 8 with `utf8mb4` encoding.

### Entity Groups

| Group | Tables | Notes |
|---|---|---|
| **Accounts** | `roles`, `users`, `user_addresses` | `users.role_id` FK to `roles`. Role/active status are server-set only |
| **Catalogue** | `categories`, `products`, `product_variants`, `product_images`, `reviews` | Prices + stock live on variants. Products use soft deletes |
| **Inventory** | `inventory_movements` | Complete audit trail of every stock change |
| **Cart & Wishlist** | `carts`, `cart_items`, `wishlists`, `wishlist_items` | Guests use session token; merged on login |
| **Coupons** | `coupons`, `coupon_categories`, `coupon_products`, `coupon_usages` | Pivot tables restrict coupons to specific products/categories |
| **Orders** | `orders`, `order_items`, `order_status_histories` | Items are snapshotted at purchase. Status changes are logged |
| **Payments** | `payments`, `payment_webhook_events` | One row per attempt. Webhooks recorded for idempotency |
| **CMS** | `pages`, `banners`, `faqs`, `settings`, `contact_enquiries`, `newsletter_subscribers`, `admin_activity_logs` | Content, config, and audit |

### Money Storage

All monetary values are stored as `DECIMAL(12,2)` in the database. PHP calculations use integer paise via the `Money` class:

```php
Money::toPaise('109.50');     // → 10950
Money::fromPaise(10950);      // → "109.50"
Money::format('123456.50');   // → "₹1,23,456.50" (Indian grouping)
```

### Migrations

12 migration files covering all tables, ordered chronologically from `2026_01_01_000001` to `2026_10_22_000012`. The last migration adds Google login fields (`google_id`, `password_login_enabled`) to the `users` table.

A hand-written SQL schema exists at `database/mysql/arsh_perfume.sql` as reference. **Always use `php artisan migrate`** as the primary route.

---

## 8. Routing

### Route Files

| File | Prefix | Name Prefix | Middleware | Purpose |
|---|---|---|---|---|
| `routes/web.php` | `/` | — | `web` | Storefront, catalogue, cart, checkout, payments, pages |
| `routes/auth.php` | `/` | — | `web` + `guest`/`auth` | Login, register, Google OAuth, password reset, logout |
| `routes/admin.php` | `/admin` | `admin.` | `web`, `auth`, `admin` | Admin panel (all controllers) |

### Storefront Routes (web.php)

| Method | URI | Controller | Name |
|---|---|---|---|
| GET | `/` | HomeController@index | `home` |
| GET | `/shop` | ShopController@index | `shop` |
| GET | `/collections` | CollectionController@index | `collections.index` |
| GET | `/collections/{slug}` | CollectionController@show | `collections.show` |
| GET | `/product/{slug}` | ProductController@show | `product.show` |
| POST | `/product/{slug}/reviews` | ReviewController@store | `reviews.store` |
| GET | `/cart` | CartController@index | `cart.index` |
| POST | `/cart/items` | CartController@store | `cart.items.store` |
| POST | `/cart/buy-now` | CartController@buyNow | `cart.buy-now` |
| PATCH | `/cart/items/{item}` | CartController@update | `cart.items.update` |
| DELETE | `/cart/items/{item}` | CartController@destroy | `cart.items.destroy` |
| POST | `/cart/coupon` | CartController@applyCoupon | `cart.coupon.apply` |
| DELETE | `/cart/coupon` | CartController@removeCoupon | `cart.coupon.remove` |
| GET | `/checkout` | CheckoutController@show | `checkout.show` |
| POST | `/checkout` | CheckoutController@store | `checkout.store` |
| GET | `/checkout/pay/{order_number}` | PaymentController@show | `checkout.pay` |
| POST | `/checkout/pay/{order_number}/verify` | PaymentController@verify | `checkout.verify` |
| POST | `/checkout/pay/{order_number}/failed` | PaymentController@failed | `checkout.failed` |
| POST | `/checkout/pay/{order_number}/simulate` | PaymentController@simulate | `checkout.simulate` |
| GET | `/checkout/upi/{order_number}` | UpiPaymentController@show | `checkout.upi` |
| POST | `/checkout/upi/{order_number}/reference` | UpiPaymentController@submitReference | `checkout.upi.reference` |
| GET | `/order-success` | OrderController@success | `order.success` |
| POST | `/webhooks/razorpay` | PaymentWebhookController@handle | `webhooks.razorpay` |
| GET | `/track-order` | TrackOrderController@index | `track-order` |
| POST | `/track-order` | TrackOrderController@lookup | `track-order.lookup` |
| GET | `/about`, `/shipping-policy`, etc. | PageController@show | `pages.*` |
| GET | `/faq` | PageController@faq | `pages.faq` |
| GET/POST | `/contact` | ContactController | `contact` / `contact.store` |
| POST | `/newsletter` | NewsletterController@store | `newsletter.store` |
| GET | `/newsletter/unsubscribe/{token}` | NewsletterController@unsubscribe | `newsletter.unsubscribe` |
| GET | `/sitemap.xml` | SeoController@sitemap | `sitemap` |
| GET | `/robots.txt` | SeoController@robots | `robots` |

**Authenticated customer routes** (middleware `auth`):

| Method | URI | Name |
|---|---|---|
| GET | `/wishlist` | `wishlist.index` |
| POST | `/wishlist/{slug}` | `wishlist.toggle` |
| GET | `/account` | `account` |
| PATCH | `/account/profile` | `account.profile.update` |
| PUT | `/account/password` | `account.password.update` |
| POST/PUT/DELETE | `/account/addresses/*` | `account.addresses.*` |
| GET | `/account/orders` | `account.orders.index` |
| GET | `/account/orders/{order_number}` | `account.orders.show` |
| POST | `/account/orders/{order_number}/cancel` | `account.orders.cancel` |

### Auth Routes (auth.php)

| Method | URI | Name | Guard |
|---|---|---|---|
| GET | `/register` | `register` | guest |
| POST | `/register` | — | guest + throttle |
| GET | `/login` | `login` | guest |
| POST | `/login` | — | guest + throttle |
| GET | `/forgot-password` | `password.request` | guest |
| POST | `/forgot-password` | `password.email` | guest + throttle |
| GET | `/reset-password/{token}` | `password.reset` | guest |
| POST | `/reset-password` | `password.store` | guest + throttle |
| GET | `/auth/google/redirect` | `auth.google` | guest + throttle |
| GET | `/auth/google/callback` | `auth.google.callback` | throttle (no guest) |
| POST | `/account/google/connect` | `account.google.connect` | auth + throttle |
| POST | `/logout` | `logout` | auth |

**There is no admin registration route.** Admins are created only via `php artisan arsh:create-admin`.

### Admin Routes (admin.php)

All prefixed with `/admin`, named `admin.*`, guarded by `web` + `auth` + `admin` middleware.

| Area | Routes | Key Actions |
|---|---|---|
| Dashboard | `GET /admin` | Revenue, order stats, stock alerts, bestsellers |
| Products | CRUD + export/import + image management | Create, edit, publish gate, image upload/delete/primary |
| Categories | CRUD + reorder + toggle active | Drag-to-reorder, active/inactive toggle |
| Inventory | Index + movements + adjust | Stock adjustments, movement history |
| Orders | Index + show + invoice + status + tracking + mark-paid + verify-upi + refund | Full order management |
| Customers | Index + show + toggle active | View customer details, enable/disable |
| Coupons | Full CRUD | Percentage/fixed, limits, restrictions |
| Banners | Full CRUD | Homepage banners |
| Pages | Index + edit + publish/unpublish + preview | CMS page management |
| FAQs | Full CRUD | FAQ management |
| Reviews | Index + approve/reject/delete | Moderation |
| Enquiries | Index + show + update | Contact form responses |
| Newsletter | Index + export | Subscriber management |
| Settings | Edit + update | Runtime business/commerce settings |
| Reports | Index + sales + inventory + customers | Analytics and exports |
| Activity Log | Index | Admin action audit trail |

---

## 9. Services Layer

All business logic lives in `app/Services/`. Controllers never contain business rules, transactions, or cross-model logic.

### OrderService

**File:** `app/Services/OrderService.php`

The central service for order creation and lifecycle management.

**`place(?User, Cart, array, ?string): Order`**
- Checks idempotency key for duplicate submissions
- Validates payment method availability
- Runs the entire order creation in a single DB transaction via `createOrder()`

**`createOrder()` (inside transaction):**
1. Asserts cart is not empty
2. Locks all variant rows `ORDER BY id` (prevents deadlocks)
3. Re-verifies each item: product active, variant purchasable, stock available
4. Locks coupon row, re-validates usage limits
5. Computes totals via `PricingService`
6. Creates order header with all shipping/billing data
7. Creates order items (snapshotted product name, price, SKU, image)
8. Deducts stock immediately via `InventoryService`
9. Creates payment row and coupon usage record
10. Records status history
11. Empties the cart (same transaction)
12. Fires `OrderPlaced` event after commit

**`transition(Order, string, ?User, ?string, array): Order`**
- Validates against `config('arsh.status_transitions')`
- Blocks advancing unpaid online orders
- Cancellation: restores stock, releases coupon usage, marks payment failed
- Delivered + COD: auto-marks payment as paid
- Shipped/Delivered: sets timestamp
- Return with `restock` option: restores stock
- Sends customer notification after commit

**`cancelByCustomer(Order, User): Order`**
- Verifies order is in customer-cancellable status
- Delegates to `transition()`

**`refund(Order, ?string, User, ?string): Order`**
- Only paid orders
- Validates refund amount against remaining refundable
- For Razorpay: sends refund to gateway
- Updates `refunded_amount`, marks `refunded` if fully refunded
- Records history

**`markCodPaid(Order, User): Order`**
- Early COD collection (before delivery)
- Validates it's a pending COD, not cancelled

### CartService

**File:** `app/Services/CartService.php`

Database-backed cart supporting both authenticated and guest users.

| Method | Purpose |
|---|---|
| `current(create)` | Get or create current cart (user-bound or session-token) |
| `count()` | Total units in cart (for header badge) |
| `add(variant, qty)` | Add to cart with stock validation |
| `updateQuantity(item, qty)` | Update line quantity (0 removes) |
| `remove(item)` | Delete a cart item |
| `clear(cart)` | Empty the cart |
| `applyCoupon(cart, code)` | Validate and apply coupon |
| `removeCoupon(cart)` | Remove coupon |
| `summary(cart, email)` | Full server-verified cart view with live prices, stock issues, coupon |
| `mergeGuestCartInto(user)` | Fold guest cart into user cart on login |

**Constants:**
- `MAX_PER_LINE = 10` — Maximum quantity of any single variant per cart

**Guest carts** use a random 40-character session token. On login, the guest cart is merged into the user's persistent cart (quantities summed, capped at stock and MAX_PER_LINE).

### PricingService

**File:** `app/Services/PricingService.php`

Pure calculation service shared by cart, checkout, and order creation. All math in integer paise.

```php
$totals = $pricing->calculate($lines, $discountPaise);
// Returns: subtotal, discount, shipping, tax, total (both paise and decimal string)
```

**Calculation flow:**
1. Sum line totals (unit_price_paise × quantity)
2. Apply discount (capped at subtotal)
3. Calculate shipping (flat rate, waived above free shipping threshold)
4. Calculate tax (inclusive or exclusive, configurable rate)
5. Compute grand total

### InventoryService

**File:** `app/Services/InventoryService.php`

Every stock change goes through this service. The variant row is locked, validated (never negative), and a movement row is written, all in one transaction.

| Method | Purpose |
|---|---|
| `adjust(variant, delta, type, note, reference, user)` | Change stock by delta (+/-). Throws if result < 0 |
| `setStock(variant, newStock, type, note, user)` | Set absolute stock level (stock take) |
| `restoreForOrder(order, type, user)` | Put an order's stock back. Idempotent via `stock_restored_at` |

**Movement types:** `sale`, `cancellation_restore`, `return_restore`, `adjustment`, `correction`, `stocktake`

### PaymentService

**File:** `app/Services/PaymentService.php`

Online payment lifecycle for Razorpay.

| Method | Purpose |
|---|---|
| `startOnlinePayment(order)` | Create gateway order (or reuse existing) |
| `verifyCheckoutCallback(order, gatewayOrderId, paymentId, signature)` | Browser callback verification |
| `recordSuccessfulPayment(order, ...)` | Idempotently mark order as paid |
| `recordFailedAttempt(order, code, description)` | Record failed payment attempt |
| `handleWebhook(eventId, payload)` | Process webhook exactly once per event ID |
| `expireUnpaidOrders(minutes)` | Cancel stale unpaid Razorpay orders (scheduler) |

**Safety rules:**
1. Gateway order must belong to the order being paid
2. HMAC signature must verify
3. Paid amount must equal order total
4. In live mode: gateway must report payment as captured
5. A payment ID can only settle one order
6. Already-paid orders are silently returned (idempotent)

### RazorpayService

**File:** `app/Services/RazorpayService.php`

Thin Razorpay REST client using Laravel's HTTP client (no SDK).

**Modes:**
| Mode | Condition | Behaviour |
|---|---|---|
| `live` | Key ID and secret configured | Real Razorpay API calls |
| `simulated` | No keys + non-production | Local fake gateway with APP_KEY-derived signing |
| `disabled` | No keys in production | Online payment unavailable |

| Method | Purpose |
|---|---|
| `mode()` / `isLive()` / `isSimulated()` | Check gateway mode |
| `isAvailable()` | Payment setting on AND gateway usable |
| `publicKey()` | Key for browser checkout widget |
| `createGatewayOrder(order)` | Create Razorpay order (real or simulated) |
| `verifySignature(orderId, paymentId, signature)` | HMAC-SHA256 checkout callback verification |
| `signForSimulation(orderId, paymentId)` | Generate signature for simulated mode |
| `verifyWebhookSignature(body, signature)` | HMAC-SHA256 webhook verification |
| `fetchPayment(paymentId)` | Fetch payment details from gateway |
| `capture(paymentId, amount)` | Capture an authorized payment |
| `refund(paymentId, amount, receipt)` | Process a refund |

### UpiPaymentService

**File:** `app/Services/UpiPaymentService.php`

Direct UPI payments with manual admin verification. Payment data is never auto-confirmed.

| Method | Purpose |
|---|---|
| `isAvailable()` | UPI enabled + valid UPI ID + payee name configured |
| `snapshot()` | Capture current UPI receiving details (stored on payment) |
| `payment(order)` | Get UPI payment row for an order |
| `paymentUri(order, payment)` | Generate `upi://pay?...` URI for QR code |
| `submitReference(order, reference)` | Customer submits UTR (does NOT mark paid) |
| `verifyReceipt(order, reference, actor)` | Admin verifies bank receipt → marks paid |

**Reference format:** 6–64 alphanumeric characters, normalised to uppercase.

**Critical rule:** A verified bank reference cannot settle a second order (unique constraint on `provider_payment_id`).

### CouponService

**File:** `app/Services/CouponService.php`

Server-side coupon validation and discount calculation.

**Validation chain (throws `CouponException` with specific reason):**
1. Coupon is active
2. Not before start date
3. Not past expiry date
4. Global usage limit not reached
5. Per-user limit not reached (checks both user_id and email)
6. Minimum order amount met
7. Eligible subtotal > 0 (respecting product/category restrictions)
8. Computed discount > 0

**Discount calculation:**
- Percentage: `eligible_subtotal × value / 100`
- Fixed amount: `value` in paise
- Capped at `max_discount` if set
- Capped at eligible subtotal (never gives more than the items are worth)

### GoogleSignInService

**File:** `app/Services/GoogleSignInService.php`

Google OAuth handling with strict security rules.

| Method | Purpose |
|---|---|
| `isAvailable()` | Google credentials configured |
| `resolveUser(googleUser, connectTo)` | Find/create/link user account |

**Security rules:**
- Google subject must have a verified email
- Existing user with same Google ID → login (if active)
- A verified Google email match automatically connects an existing active account on first sign-in, preserving its password and role; future sign-ins use the stable Google subject. An existing different Google identity cannot be overwritten.
- Explicit connect requires same email as local account
- New accounts get `customer` role only (never admin)
- Google-only accounts have `password_login_enabled = false`
- Disabled accounts are rejected

### ActivityLogger

**File:** `app/Services/ActivityLogger.php`

Audit trail for admin actions, recording user, action, subject, description, IP, and user agent.

### ReportService

**File:** `app/Services/ReportService.php`

Reporting service with a consistent revenue definition used everywhere:

```
Revenue = Orders where payment_status IN ('paid', 'refunded')
          AND status NOT IN ('cancelled', 'returned')
          Per order: grand_total - refunded_amount
```

| Method | Purpose |
|---|---|
| `revenue(from, to)` | Sum net revenue for period |
| `dashboard()` | All dashboard metrics (revenue, orders, stock, bestsellers) |
| `salesSeries(from, to)` | Daily revenue + orders (zero-filled, for Chart.js) |
| `bestSellers(limit, from, to)` | Top products by quantity sold |
| `salesRows(from, to)` | Order rows for sales report/CSV |
| `inventoryRows()` | Variant-level stock report |
| `customerRows()` | Customer list with order counts |

---

## 10. Models & Relationships

### User

| Relation | Type | Target |
|---|---|---|
| `role()` | BelongsTo | Role |
| `addresses()` | HasMany | UserAddress |
| `orders()` | HasMany | Order |
| `cart()` | HasOne | Cart |
| `wishlist()` | HasOne | Wishlist |
| `reviews()` | HasMany | Review |

**Mass assignment protection:** `role_id` and `is_active` are intentionally NOT in `$fillable`. No public form can promote a user to admin.

**Key methods:** `isAdmin()`, `lifetimeTotal()`, `scopeCustomers()`

### Product

| Relation | Type | Target |
|---|---|---|
| `category()` | BelongsTo | Category |
| `images()` | HasMany | ProductImage |
| `variants()` | HasMany | ProductVariant |
| `reviews()` | HasMany | Review |
| `approvedReviews()` | HasMany | Review (filtered) |

**Scopes:** `active()`, `featured()`, `bestsellers()`, `search()`, `visibleInShop()`

**Attributes:** `primary_image_url`, `starting_price`, `starting_list_price`, `in_stock`, `average_rating`

**Publishing gate:** `missingFields()` returns what's needed before a product can be set to Active: name, description, image, category, at least one active priced variant.

Uses **soft deletes** — deleted products remain in order history.

### Order

| Relation | Type | Target |
|---|---|---|
| `user()` | BelongsTo | User |
| `items()` | HasMany | OrderItem |
| `payments()` | HasMany | Payment |
| `latestPayment()` | HasOne | Payment (latest) |
| `statusHistories()` | HasMany | OrderStatusHistory |
| `coupon()` | BelongsTo | Coupon |

**URL routing:** Uses `order_number` (e.g., `AP261027xxxxx`), never the sequential `id`.

**Key methods:** `isPaid()`, `isCancellable()`, `canBeCancelledByCustomer()`, `shippingAddressOneLine()`, `timelineSteps()`

**Scope:** `revenue()` — the consistent revenue query used everywhere.

### ProductVariant

Prices and stock live on variants, not products. A product has many variants (sizes like 3ml, 6ml, 12ml).

**Key attributes:**
- `price` — list price
- `sale_price` — sale price (nullable)
- `effective_price` — computed: sale_price if on sale, otherwise price
- `is_on_sale` — computed: sale_price set and less than price
- `is_purchasable` — computed: active + priced
- `stock` — current stock level
- `low_stock_threshold` — per-variant threshold

---

## 11. Authentication & Authorization

### Authentication

Custom controllers (no Breeze/Jetstream):

| Flow | Controller | Notes |
|---|---|---|
| Email registration | `RegisterController` | Creates customer role only |
| Email login | `LoginController` | Merges guest cart on login |
| Forgot password | `ForgotPasswordController` | Sends reset link |
| Reset password | `ResetPasswordController` | Token-based reset |
| Google OAuth | `GoogleLoginController` | Redirect → callback → resolve |
| Logout | `LoginController::destroy` | POST only |

### Middleware

| Middleware | Applied To | Behaviour |
|---|---|---|
| `EnsureUserIsActive` | All web routes | Disabled users are immediately logged out |
| `EnsureUserIsAdmin` (`admin`) | All `/admin` routes | Checks user.role.name === 'admin' AND is_active |

### Authorization

| Policy | Method | Rule |
|---|---|---|
| `OrderPolicy::view` | `account/orders/{id}` | user_id must match |
| `OrderPolicy::cancel` | `account/orders/{id}/cancel` | Must be owner AND in cancellable status |
| `UserAddressPolicy` | Address CRUD | Addresses belong to authenticated user |

### Role System

Two roles created by `RoleSeeder`:

| Role | ID | Capabilities |
|---|---|---|
| `admin` | 1 | Full admin panel access |
| `customer` | 2 | Storefront shopping, account management |

Roles are assigned by code only:
- Registration (email or Google) → `customer`
- `arsh:create-admin` / `AdminUserSeeder` → `admin`

---

## 12. Payment Processing

### Payment Methods

| Method | Key | Flow |
|---|---|---|
| Cash on Delivery | `cod` | Order placed → delivered → auto-marked paid |
| Razorpay | `razorpay` | Order placed → gateway order → browser pay → verify → confirmed |
| UPI QR | `upi` | Order placed → QR shown → customer submit UTR → admin verify receipt |

### Razorpay Flow (Detailed)

```
1. CheckoutController::store()
   → OrderService::place() creates order + payment row (status: 'created')

2. Redirect → PaymentController::show()
   → PaymentService::startOnlinePayment() creates Razorpay order ID

3. Browser: Razorpay Checkout.js widget opens
   → Customer pays

4a. Success callback → PaymentController::verify()
    → PaymentService::verifyCheckoutCallback()
    → Verify HMAC signature (order_id|payment_id signed with key_secret)
    → (Live mode) Fetch payment from Razorpay, capture if authorized
    → Verify amount matches
    → recordSuccessfulPayment() → order.payment_status = 'paid'
    → Auto-confirm: pending → confirmed

4b. Failure callback → PaymentController::failed()
    → Records error code/description, order stays pending

5. Webhook → PaymentWebhookController::handle()
   → PaymentService::handleWebhook()
   → Verify webhook HMAC (raw body signed with webhook_secret)
   → Record event (unique event_id prevents duplicate processing)
   → payment.captured: recordSuccessfulPayment() (idempotent)
   → payment.failed: record error
   → refund.processed: mirror refund to local records
```

### Simulated Gateway

When `RAZORPAY_KEY_ID` and `RAZORPAY_KEY_SECRET` are empty and `APP_ENV` is not `production`:
- Gateway orders get IDs like `order_sim_xxxxxxxxxxxx`
- Signing uses `SHA256(APP_KEY|razorpay-simulated)` as the secret
- The same verification code path runs
- A "Simulate Payment" button appears on the pay page
- **Cannot be enabled in production**

### UPI QR Flow

```
1. Checkout with payment_method = 'upi'
   → OrderService::place() creates order + payment (meta snapshots current UPI config)

2. Redirect → UpiPaymentController::show()
   → Generates upi://pay? URI
   → Browser-side qrcode library renders QR

3. Customer scans QR → pays in their UPI app
   → Returns to page → submits UTR/transaction reference

4. UpiPaymentService::submitReference()
   → Stores reference in payment.meta (does NOT mark paid)
   → Records status history (unverified reference)

5. Admin → Orders → Order → "Verify UPI payment received"
   → UpiPaymentService::verifyReceipt()
   → Validates reference format
   → Checks reference not used for another order
   → Marks payment + order as paid
```

### Webhook CSRF Exemption

The Razorpay webhook endpoint (`/webhooks/razorpay`) is excluded from CSRF verification in `bootstrap/app.php` because it's authenticated by HMAC signature instead.

---

## 13. Inventory Management

### Stock Model

- Stock is stored on `product_variants.stock` (integer)
- Every change creates an `inventory_movements` row (full audit trail)
- `low_stock_threshold` per variant (default from settings)

### Movement Types

| Type | Source | Delta |
|---|---|---|
| `sale` | OrderService::place() | Negative (stock out) |
| `cancellation_restore` | Order cancelled | Positive (stock in) |
| `return_restore` | Order returned (optional) | Positive (stock in) |
| `adjustment` | Admin manual adjust | Either |
| `correction` | Admin stock take | Either |
| `stocktake` | Admin set absolute level | Either |

### Safeguards

- **Never negative:** `adjust()` throws if result < 0
- **Pessimistic locking:** Variant row locked before reading stock
- **Once-only restore:** `order.stock_restored_at` prevents double-restoring cancelled/returned orders
- **Sales count tracking:** `products.sales_count` is incremented/decremented alongside stock

### Admin Inventory Pages

- **Inventory Index** (`/admin/inventory`) — All variants with current stock, threshold, status
- **Movement History** (`/admin/inventory/movements`) — Complete ledger
- **Adjust Stock** (`POST /admin/inventory/{variant}/adjust`) — Manual adjustment with reason

---

## 14. Cart & Checkout Flow

### Cart Architecture

```
Guest                                    Authenticated
┌──────────────┐                        ┌──────────────┐
│ Session token │ ──── merge on ────▶   │ user_id FK   │
│ (40 chars)    │       login           │ (persistent) │
└──────────────┘                        └──────────────┘
       │                                       │
       ▼                                       ▼
   carts table                             carts table
       │                                       │
       ▼                                       ▼
   cart_items table                        cart_items table
```

### Checkout Flow

```
1. GET /checkout
   → CartService::summary() validates everything server-side
   → Shows shipping form, payment method selection
   → Pre-fills from saved addresses (authenticated users)

2. POST /checkout
   → CheckoutRequest validates all fields
   → OrderService::place(user, cart, data, ip)
   → Returns Order

3. Redirect based on payment method:
   → COD: redirect to /order-success?order={number}
   → Razorpay: redirect to /checkout/pay/{order_number}
   → UPI: redirect to /checkout/upi/{order_number}
```

### Cart Summary (server-verified every view)

Each time the cart is displayed, `CartService::summary()`:
- Loads all items with variants and products
- Checks each: product active? Variant purchasable? Stock available?
- Flags issues (out of stock, limited stock, unavailable)
- Re-validates coupon (may have expired or cart changed)
- Computes totals via PricingService
- Returns structured data for the view

---

## 15. Order Lifecycle

### Status Flow

```
                    ┌─────────────────┐
                    │     PENDING     │
                    └────────┬────────┘
                             │
                    confirmed│             cancelled
                             ▼                 ▲
                    ┌────────────────┐         │
                    │   CONFIRMED    │─────────┤
                    └────────┬───────┘         │
                             │                 │
                   processing│                 │
                             ▼                 │
                    ┌────────────────┐         │
                    │  PROCESSING    │─────────┘
                    └────────┬───────┘
                             │
                      shipped│
                             ▼
                    ┌────────────────┐
                    │    SHIPPED     │
                    └───────┬┬──────┘
                            ││
         out_for_delivery ──┘│       delivered
                             │          │
                    ┌────────▼───┐      ▼
                    │OUT FOR DEL.│  ┌──────────┐
                    └────────┬───┘  │DELIVERED │
                             │      └────┬─────┘
                    delivered│           │returned
                             │      ┌────▼─────┐
                             └─────▶│ RETURNED │
                                    └──────────┘

Terminal states: CANCELLED, RETURNED (no further transitions)
```

### Transition Rules (config/arsh.php)

| From | Allowed To |
|---|---|
| `pending` | `confirmed`, `cancelled` |
| `confirmed` | `processing`, `cancelled` |
| `processing` | `shipped`, `cancelled` |
| `shipped` | `out_for_delivery`, `delivered`, `returned` |
| `out_for_delivery` | `delivered`, `returned` |
| `delivered` | `returned` |
| `cancelled` | (none) |
| `returned` | (none) |

### Customer Cancellation

Customers can cancel their own orders only when status is `pending` or `confirmed`.

### Payment-Gated Advancement

Online payment orders (`razorpay`, `upi`) cannot advance past `pending` until `payment_status = 'paid'`. The only allowed transition for unpaid online orders is `cancelled`.

### Automatic Actions

| Event | Action |
|---|---|
| Order placed (Razorpay) + payment verified | Auto-transition: pending → confirmed |
| Delivered + COD | Auto-mark: payment_status → paid |
| Unpaid Razorpay > 30 min | Scheduler cancels and restores stock |
| Cancellation | Stock restored, coupon usage released |

---

## 16. Coupon System

### Coupon Types

| Type | Value Meaning |
|---|---|
| `percent` | Percentage off eligible subtotal |
| `fixed` | Fixed amount in rupees |

### Restrictions

- **Global usage limit** — max total uses across all customers
- **Per-user limit** — max uses per customer (checked by user_id AND email)
- **Minimum order amount** — subtotal must exceed threshold
- **Max discount** — caps percentage discounts
- **Product restrictions** — coupon applies only to specific products (via `coupon_products` pivot)
- **Category restrictions** — coupon applies only to specific categories (via `coupon_categories` pivot)
- **Date range** — `starts_at` / `expires_at`

### Lifecycle

1. Customer enters code → `CartService::applyCoupon()` validates
2. Code stored on `carts.coupon_code`
3. Every cart view re-validates (expired/changed cart → auto-removed)
4. At checkout: `OrderService` locks coupon row, re-validates, creates `coupon_usages` row
5. On cancellation: usage deleted, `used_count` decremented

---

## 17. Admin Panel

### Dashboard (`/admin`)

Powered by `ReportService::dashboard()`:

- Today's revenue, month revenue, total revenue
- Total orders, pending, completed, cancelled
- Total customers
- Active products count
- Low stock and out-of-stock alerts
- Recent orders list
- Bestsellers

Charts rendered with Chart.js in `resources/js/admin.js`.

### Product Management

- CRUD with publishing gate (requires name, description, image, category, priced variant)
- Multi-image upload with primary image selection
- Images stored under random names (never original filenames)
- Image validation: type and size
- Variant management (sizes/prices/stock per variant)
- CSV export (import route exists but method not implemented)
- Soft deletes (archived products preserved in order history)

### Order Management

- Filter by status, search by order number
- Status transitions (validated against config)
- Invoice generation and print
- Tracking number management
- Mark COD as paid
- Verify UPI payment receipt
- Full/partial refund processing
- Status history timeline
- CSV export

### Activity Log

Admin actions are logged to `admin_activity_logs` via `ActivityLogger::log()`. Records:
- Which admin performed the action
- What was changed (action + subject)
- Description
- IP address and user agent

---

## 18. CMS & Content

### Pages

- Pre-seeded pages: About, Shipping Policy, Returns & Refunds, Privacy, Terms, Cancellation, Payment Info
- All seeded as **unpublished** with `[OWNER TO CONFIRM]` placeholders
- Admin can edit, preview, publish, and unpublish
- Unpublished pages return 404 for the public

### Banners

- Full CRUD for homepage hero banners
- Image upload with title, subtitle, link, sort order

### FAQs

- Full CRUD
- Displayed on `/faq` page

### Settings

Runtime settings cover:
- Business info (name, contact, address, GSTIN, logo)
- Social links (Instagram, Facebook, YouTube, WhatsApp)
- Commerce (tax, shipping rates, free shipping threshold, payment methods)
- UPI payment configuration
- Invoice prefix and footer
- Notification preferences
- Homepage section visibility

---

## 19. Frontend Architecture

### Build System

**Vite 7** with three entry points:

```js
// vite.config.js
input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js']
```

- `app.css` — Tailwind CSS entry (imports storefront, admin, auth, UPI, service-page styles)
- `app.js` — Alpine.js, storefront motion, theme helpers, lazy UPI QR import
- `admin.js` — Chart.js setup, admin-specific behaviour

### CSS

- **Tailwind CSS 4** via `@tailwindcss/vite` plugin
- Separate CSS modules: `storefront.css`, `admin.css`, `auth.css`, `upi.css`, `service-pages.css`
- Design direction: midnight/amber luxury, brass accents, textured dark surfaces

### JavaScript (No React)

- **Alpine.js 3** — reactive components (dropdowns, modals, cart updates, filtering)
- **Framer Motion DOM APIs** — storefront animations (not the React variant)
- **Chart.js** — admin dashboard charts (revenue over time, order stats)
- **qrcode** — browser-side UPI QR code generation (no external QR service)
- **Axios** — AJAX requests

### Blade Components

| Component | Purpose |
|---|---|
| `product-card` | Product card for shop/home listings |
| `collection-card` | Category card |
| `fragrance-sculpture` | CSS 3D decorative element |
| `google-sign-in` | Google login button (disabled when unconfigured) |
| `page-heading` | Consistent page headers |
| `help-navigation` | Footer/policy page navigation |
| `admin-icon` | Admin panel icon set |

### Layouts

| Layout | Used By |
|---|---|
| `layouts/app.blade.php` | All storefront pages |
| `layouts/home.blade.php` | Homepage (extends app) |
| `layouts/admin.blade.php` | All admin pages |
| `account/layout.blade.php` | Customer account pages |
| `errors/layout.blade.php` | Error pages |

---

## 20. Testing

### Test Infrastructure

- **Framework:** PHPUnit ^11.5
- **Database:** In-memory SQLite (RefreshDatabase per test)
- **Cache/Session/Mail:** Array drivers
- **Queue:** Synchronous (jobs run immediately)
- **Base class:** `FeatureTestCase` (seeds roles)

### Test Files

| File | Coverage |
|---|---|
| `Unit/MoneyTest.php` | Integer paise conversion, Indian digit grouping |
| `Feature/AccessControlTest.php` | Login requirement, admin/customer separation |
| `Feature/AdminProductTest.php` | Product CRUD, publishing gates, sale price rules, image validation |
| `Feature/AdminDashboardTest.php` | Dashboard rendering and metrics |
| `Feature/CheckoutTest.php` | Stock limits, COD orders, price snapshots, coupons, unsellable variants |
| `Feature/OrderSecurityTest.php` | IDOR protection, guest visibility, status transitions, stock restore |
| `Feature/InventoryTest.php` | Stock adjustments, movements, negative prevention, stocktake |
| `Feature/PaymentWebhookTest.php` | Bad signature rejection, capture, duplicates, wrong amount |
| `Feature/UpiPaymentTest.php` | UPI checkout, verification, access controls, inventory |
| `Feature/GoogleLoginTest.php` | Google OAuth flow, account linking, security rules |
| `Feature/StorefrontTest.php` | Page rendering, catalogue display |

### Running Tests

```bash
php artisan test                    # Full suite
php artisan test --compact          # Compact output
php artisan test --filter=Checkout  # Specific test
```

### Known Test Issues (as of 2026-10-24)

- **68 passed, 2 failed, 389 assertions**
- Image generation test fails because CLI PHP lacks GD extension
- `PaymentWebhookTest:91` expects payment status `pending` but implementation uses `created`

---

## 21. Deployment

### Production Checklist

1. **Server:** PHP 8.2+, MySQL 8, HTTPS (Let's Encrypt), document root = `public/`
2. **Code:** Deploy from version control. Create production `.env` on server, `APP_DEBUG=false`
3. **Install:** `composer install --no-dev --optimize-autoloader` then `npm ci && npm run build`
4. **Database:** `php artisan migrate --force`. Seed only on fresh database
5. **Optimise:** `php artisan config:cache route:cache view:cache event:cache`
6. **Permissions:** Web user writable: `storage/`, `bootstrap/cache/`. Nothing else
7. **Storage link:** `php artisan storage:link`
8. **Session cookie:** `SESSION_SECURE_COOKIE=true`
9. **Cron and worker:** See [Scheduled Tasks & Queues](#23-scheduled-tasks--queues)
10. **Razorpay:** Test-mode keys first, verify checkout flow, then live keys
11. **Policy pages:** Owner reviews and publishes in admin
12. **Backups:** Daily MySQL dumps + `storage/app/public` backups off-server

### Nginx Configuration

```nginx
server {
    root /path/to/arsh-perfume/public;
    index index.php;

    location / {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Apache Configuration

```apache
DocumentRoot "/path/to/arsh-perfume/public"
<Directory "/path/to/arsh-perfume/public">
    AllowOverride All
    Require all granted
</Directory>
```

Requires `mod_rewrite` enabled. The `public/.htaccess` handles URL rewriting.

---

## 22. Security Model

### Access Control

| Layer | Mechanism | Protection |
|---|---|---|
| Admin routes | `EnsureUserIsAdmin` middleware | Checks role + active status from database |
| Admin registration | No route exists | CLI command only (`arsh:create-admin`) |
| Role assignment | Not in `$fillable` | Server code only (registration, seeder, CLI) |
| Disabled accounts | `EnsureUserIsActive` middleware | Logged out on next request |
| Customer orders | `OrderPolicy` | user_id match required (IDOR protection) |
| Guest orders | `OrderAccess` | Browser session that placed the order |
| Customer addresses | `UserAddressPolicy` | Ownership check |

### Payment Security

| Threat | Protection |
|---|---|
| Amount tampering | Server computes totals; amount compared with gateway report |
| Replay attacks | Payment ID can only settle one order |
| Forged callbacks | HMAC-SHA256 signature verification |
| Forged webhooks | HMAC-SHA256 over raw request body |
| Duplicate webhooks | Unique event ID in `payment_webhook_events` |
| Double-click orders | Idempotency key in orders table |
| UPI reference reuse | Unique `provider_payment_id` constraint |

### Input Security

| Vector | Protection |
|---|---|
| Mass assignment | `$fillable` whitelists; `role_id`/`is_active` excluded |
| CSRF | Laravel CSRF middleware (webhook exempted, uses HMAC) |
| File uploads | Type + size validation, random filenames |
| SQL injection | Eloquent parameterised queries throughout |
| XSS | Blade `{{ }}` auto-escaping |
| Rate limiting | Throttle middleware on login, register, checkout, contact, OAuth, password reset, reviews, coupons, tracking |

### Data Protection

- `.env` is in `.gitignore` — never committed
- `RAZORPAY_KEY_SECRET` and `RAZORPAY_WEBHOOK_SECRET` are server-side only
- Only `RAZORPAY_KEY_ID` is sent to browser (Razorpay requirement)
- Passwords use bcrypt hashing (configurable rounds)
- `google_id` is in `$hidden` (never serialised)

---

## 23. Scheduled Tasks & Queues

### Scheduler

Add this cron entry on the server:

```cron
* * * * * cd /path/to/arsh-perfume && php artisan schedule:run >> /dev/null 2>&1
```

**Scheduled tasks:**
| Task | Frequency | Purpose |
|---|---|---|
| `orders:expire-unpaid` | Every 10 minutes | Cancel unpaid Razorpay orders > 30 min and restore stock |
| Failed job pruning | Daily | Clean up failed_jobs table |

**Note:** UPI orders awaiting manual verification are **not** expired by the scheduler. Cancel them from admin when appropriate.

### Queue Worker

Order notifications are queued. Run a persistent worker:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Use Supervisor or systemd on Linux to keep the worker running. After each deployment:

```bash
php artisan queue:restart
```

---

## 24. API Reference: Helper Functions

Global helper functions registered in `app/Support/helpers.php`:

### `setting(string $key, mixed $default = null): mixed`

Read a runtime setting. Checks the `settings` table first, then falls back to `config/arsh.php` defaults, then `$default`.

```php
setting('currency_symbol', '₹');
setting('cod_enabled', true);
```

### `money(string|int|float|null $amount): string`

Format a monetary value with Indian digit grouping and currency symbol.

```php
money('123456.50');  // → "₹1,23,456.50"
money(109);          // → "₹109"
```

### `media_url(?string $path, ?string $fallback = null): string`

Generate a public URL for a stored media file, or the placeholder SVG when no path is set. Handles local `public` disk and cloud `s3` disk transparently.

```php
media_url('products/abc123.jpg');  // → "/storage/products/abc123.jpg"
media_url(null);                   // → "/images/placeholder.svg"
```

### `order_status_label(string $status): string`

Human-readable status label from `config('arsh.order_statuses')`.

```php
order_status_label('out_for_delivery');  // → "Out for Delivery"
```

### Money Class (`App\Support\Money`)

```php
Money::toPaise('109.50');     // → 10950 (int)
Money::toPaise(null);         // → 0
Money::fromPaise(10950);      // → "109.50" (string)
Money::fromPaise(10000);      // → "100.00" (string)
Money::format('123456.50');   // → "₹1,23,456.50" (Indian grouping)
Money::format('109');         // → "₹109"
```

---

## 25. Known Issues & Future Work

### Known Issues

| Issue | Details |
|---|---|
| README is stale | Opening status note says "never executed" — dependencies, builds and tests already exist |
| Missing `import()` method | `admin.php` routes `products/import` but `ProductController` has no `import` method |
| 2 failing tests | GD extension absent in CLI; webhook test expects `pending` but code uses `created` |
| No git history | All source is untracked; no committed baseline exists |

### Not Yet Implemented

| Feature | Notes |
|---|---|
| CSV product import | Route exists, method does not |
| Drag-and-drop category ordering | Currently a plain number field |
| SMS/WhatsApp notifications | Email only |
| Multi-currency support | INR only |
| Multiple languages | English only |
| Elasticsearch/Meilisearch | MySQL LIKE search only |
| Image optimization/CDN | Direct file storage |

### Seeded Data Caveats

- **Products** are seeded as **drafts** with stock 0. Three products have no price. Publish only after the owner supplies missing details
- **Policy pages** are seeded as **unpublished** with `[OWNER TO CONFIRM]` placeholders. Owner must review and replace text before publishing

---

## Appendix A: Configuration File Reference

| Config File | Key Contents |
|---|---|
| `config/app.php` | App name, env, timezone (Asia/Kolkata), locale, encryption |
| `config/arsh.php` | Business rules: statuses, transitions, payment methods, settings defaults |
| `config/auth.php` | Authentication guards and providers |
| `config/database.php` | MySQL (primary) and SQLite (testing) connections |
| `config/filesystems.php` | Disk configuration (local, public, s3) |
| `config/services.php` | Razorpay and Google OAuth credentials |
| `config/session.php` | Session driver and configuration |

## Appendix B: Artisan Commands

| Command | Purpose |
|---|---|
| `php artisan arsh:create-admin {email}` | Create an admin user (prompts for password) |
| `php artisan orders:expire-unpaid` | Cancel stale unpaid online orders |
| `php artisan serve` | Start development server |
| `php artisan migrate --seed` | Run migrations and seed data |
| `php artisan storage:link` | Create public storage symlink |
| `php artisan test` | Run PHPUnit test suite |
| `php artisan route:list` | List all registered routes |

## Appendix C: Related Documentation

| Document | Path | Contents |
|---|---|---|
| README | `README.md` | Setup and deployment guide (partially stale) |
| Database Reference | `docs/DATABASE_REFERENCE.md` | Generated table/column documentation |
| Routes Reference | `docs/ROUTES_REFERENCE.md` | Generated route listing |
| Settings Reference | `docs/SETTINGS_REFERENCE.md` | All available settings with types and defaults |
| Google Login Setup | `docs/google-login-setup.md` | Google OAuth configuration guide |
| Design System | `design-system/arsh-perfume/MASTER.md` | Visual design direction and principles |
| Project Memory | `docs/PROJECT_MEMORY.md` | Architecture overview from prior examination |
