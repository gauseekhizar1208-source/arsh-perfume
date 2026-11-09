# ARSH PERFUME — Architecture Overview

> How the system is structured, how its parts interact, and the reasoning behind the design.

---

## Table of Contents

1. [High-Level Architecture](#1-high-level-architecture)
2. [Request Lifecycle](#2-request-lifecycle)
3. [Layer Responsibilities](#3-layer-responsibilities)
4. [Service Layer Design](#4-service-layer-design)
5. [Data Flow Diagrams](#5-data-flow-diagrams)
6. [Authentication & Authorisation](#6-authentication--authorisation)
7. [Payment Architecture](#7-payment-architecture)
8. [Inventory & Stock Management](#8-inventory--stock-management)
9. [Event System](#9-event-system)
10. [Frontend Architecture](#10-frontend-architecture)
11. [Configuration Architecture](#11-configuration-architecture)
12. [Security Architecture](#12-security-architecture)
13. [Error Handling](#13-error-handling)
14. [Scheduled Tasks & Background Jobs](#14-scheduled-tasks--background-jobs)
15. [Design Decisions & Trade-offs](#15-design-decisions--trade-offs)

---

## 1. High-Level Architecture

The application is a **Laravel 12 monolith** with two interfaces served from a single codebase:

```
┌─────────────────────────────────────────────────────────────────────┐
│                          Browser                                    │
│       Customer Storefront              Admin Back Office            │
│    (Blade + Alpine.js + Tailwind)   (Blade + Alpine.js + Chart.js)  │
└──────────────┬──────────────────────────────┬───────────────────────┘
               │ HTTP                         │ HTTP
               ▼                              ▼
┌──────────────────────────┐   ┌──────────────────────────┐
│   routes/web.php         │   │   routes/admin.php       │
│   routes/auth.php        │   │   middleware: web+auth+   │
│   middleware: web        │   │     admin                │
└─────────┬────────────────┘   └──────────┬───────────────┘
          │                               │
          ▼                               ▼
┌─────────────────────────────────────────────────────────┐
│                 Controllers (thin)                       │
│   Validate input → delegate to Services → return view   │
└─────────────────────────┬───────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│                   Services Layer                         │
│  OrderService · CartService · PricingService ·           │
│  InventoryService · PaymentService · RazorpayService ·   │
│  UpiPaymentService · CouponService · GoogleSignInService │
│  ActivityLogger · ReportService · AdminProvisioner        │
└──────────┬──────────────────────────────┬───────────────┘
           │                              │
           ▼                              ▼
┌──────────────────────┐   ┌──────────────────────────────┐
│   Eloquent Models    │   │   External Services          │
│   (DB via MySQL 8)   │   │   · Razorpay REST API        │
│                      │   │   · Google OAuth (Socialite)  │
│                      │   │   · SMTP mail                 │
└──────────────────────┘   └──────────────────────────────┘
```

There is **no SPA, no Livewire, no Inertia**. Every page is server-rendered with Blade templates. Client-side interactivity uses Alpine.js for reactive UI, Framer Motion's DOM API for storefront animations, and Chart.js for admin dashboard charts.

---

## 2. Request Lifecycle

A typical request passes through these layers:

```
Browser → Apache/Nginx → public/index.php → Laravel bootstrap
  → Global Middleware (session, CSRF, EnsureUserIsActive)
    → Route group middleware (auth, admin)
      → Controller method
        → Form Request validation (if applicable)
          → Service method (business logic + DB transaction)
            → Model (Eloquent query + relationship)
              → MySQL
            ← Response built
          ← Service returns data
        ← Controller builds view / redirect
      ← Blade template rendered
    ← HTML response
  ← Middleware post-processing
← Response to browser
```

### Middleware Stack

| Middleware | Applied to | Purpose |
|---|---|---|
| `web` (Laravel default) | All web routes | Session, CSRF, cookies, encryption |
| `EnsureUserIsActive` | All web routes (appended) | Disabled accounts are logged out on next request |
| `auth` | Customer account + admin routes | Must be signed in |
| `admin` (`EnsureUserIsAdmin`) | All `/admin/*` routes | Checks `role_id` + `is_active` from DB |
| `throttle:*` | Checkout, login, contact, etc. | Rate limiting by IP or user ID |

### CSRF Exception

The Razorpay webhook endpoint (`/webhooks/razorpay`) is exempt from CSRF verification. It is instead authenticated by HMAC-SHA256 signature verification over the raw request body.

---

## 3. Layer Responsibilities

```
┌────────────────────┬──────────────────────────────────────────────┐
│      Layer          │  Responsibility                              │
├────────────────────┼──────────────────────────────────────────────┤
│  Routes            │  URL → Controller mapping, middleware groups │
│  Controllers       │  HTTP concerns only: validate, delegate,     │
│                    │  return views/redirects/JSON                 │
│  Form Requests     │  Input validation rules (CheckoutRequest,    │
│                    │  ProductRequest)                              │
│  Services          │  All business logic, DB transactions,        │
│                    │  external API calls                          │
│  Models            │  Data access, relationships, scopes,         │
│                    │  attribute casting, accessors                │
│  Support           │  Shared utilities (Money, OrderAccess,       │
│                    │  helper functions)                           │
│  Events/Listeners  │  Side effects decoupled from core flow      │
│  Notifications     │  Email formatting and delivery               │
│  Policies          │  Authorisation rules for model operations    │
│  Blade Views       │  HTML rendering, layout composition          │
│  JS/CSS            │  Client-side interactivity and styling       │
└────────────────────┴──────────────────────────────────────────────┘
```

**Controllers never contain business logic.** The rule is: if a second controller needs the same logic, it must come from a service. This makes the checkout flow testable without HTTP and keeps order/payment/inventory rules in one place.

---

## 4. Service Layer Design

All services in `app/Services/` are registered as **singletons** in `AppServiceProvider`. They receive their dependencies via constructor injection.

### Dependency Graph

```
OrderService
  ├── PricingService
  ├── CouponService
  ├── InventoryService
  ├── RazorpayService
  └── UpiPaymentService

PaymentService
  ├── RazorpayService
  └── OrderService

CartService
  ├── PricingService
  └── CouponService
```

### Service Boundaries

| Service | Owns | Key Rule |
|---|---|---|
| `OrderService` | Order creation, status transitions, cancellation | Everything in one DB transaction; idempotency key prevents double-clicks |
| `CartService` | Cart CRUD, cart ↔ user binding, merge on login | Guests get a session-token cart; merged into user cart on login |
| `PricingService` | Totals calculation (subtotal, tax, shipping, discount) | Pure calculation — no side effects; shared by cart page, checkout page, and order creation |
| `InventoryService` | Stock adjustments, movement logging | Every change is `SELECT ... FOR UPDATE` + log in `inventory_movements`; stock never goes negative |
| `PaymentService` | Gateway order creation, verification, expiry | An order is never marked paid without signature + amount verification |
| `RazorpayService` | Razorpay REST API calls, signature verification | Three modes: `live`, `simulated`, `disabled`. The simulated mode mirrors the real verification code path |
| `UpiPaymentService` | UPI QR generation data, reference submission, admin verification | Customer references never mark an order paid; only admin verification does |
| `CouponService` | Coupon validation, discount calculation, usage tracking | Validates min amount, date range, usage limits, product/category scope |
| `GoogleSignInService` | Google OAuth user resolution and account linking | First sign-in may link a verified matching email to an active account; existing Google identities and roles are preserved |
| `ActivityLogger` | Admin audit trail | Static `log()` writes IP, user agent, action, and affected entity |
| `ReportService` | Revenue, product, customer analytics | Revenue = orders where `payment_status ∈ {paid, refunded}` AND `status ∉ {cancelled, returned}` |
| `AdminProvisioner` | Admin account creation/promotion | Used by both the `arsh:create-admin` CLI command and the database seeder |

---

## 5. Data Flow Diagrams

### Checkout Flow (COD)

```
Customer fills checkout form
  │
  ▼
CheckoutController::store()
  │  Validates via CheckoutRequest
  │  Reads cart, calls OrderService::place()
  ▼
OrderService::place()  ─── DB::transaction ───────────────────────────┐
  │  Check idempotency key (return existing order if duplicate)       │
  │  Lock variant rows (ordered by id to prevent deadlocks)           │
  │  Re-verify stock, prices, product availability                    │
  │  Deduct stock via InventoryService::adjust()                      │
  │  Log inventory_movements (type: checkout_reserve)                 │
  │  Lock and validate coupon, record usage                           │
  │  Create order + order_items (snapshot prices)                     │
  │  Create payment row (status: paid for COD)                        │
  │  Record order_status_histories entry                              │
  ├───────────────────────────────────────────────────────────────────┘
  │
  ▼
event(OrderPlaced)  →  SendOrderPlacedNotifications (queued)
  │                      ├── OrderPlacedNotification → customer email
  │                      └── NewOrderAdminNotification → admin email
  ▼
Redirect to order success page + clear cart
```

### Checkout Flow (Razorpay Online Payment)

```
Customer fills checkout form (payment_method = razorpay)
  │
  ▼
OrderService::place()
  │  Same as COD but payment row starts as status: pending
  │  Stock is reserved (deducted) during order creation
  ▼
Redirect → PaymentController::show()
  │  PaymentService::startOnlinePayment()
  │  Creates Razorpay gateway order (or reuses on refresh)
  │  Renders Razorpay Checkout JS with gateway order ID
  ▼
Razorpay Checkout (in browser)
  │  Customer pays in Razorpay's modal
  ▼
Two verification paths (BOTH are safe; whichever arrives first marks paid):

  Path A: Browser callback                Path B: Razorpay webhook
  ───────────────────────                  ─────────────────────────
  POST /checkout/pay/{order}/verify        POST /webhooks/razorpay
  │                                        │
  │  Verify HMAC signature                 │  Verify webhook HMAC
  │  (order_id + payment_id               │  (raw body + webhook secret)
  │   signed with key_secret)             │
  │                                        │  Check payment_webhook_events
  │  Check paid amount = order total       │  for duplicate event ID
  │                                        │
  └──── PaymentService::markPaid() ────────┘
          │  Idempotent: second call is a no-op
          │  Updates payment row + order.payment_status
          ▼
       event(OrderPlaced) if not already dispatched
```

### Checkout Flow (UPI QR)

```
Customer selects UPI QR at checkout
  │
  ▼
OrderService::place()
  │  Creates order with payment_method: upi, payment status: pending
  │  Snapshots UPI details (payee name, UPI ID) into payment.meta
  ▼
Redirect → UpiPaymentController::show()
  │  Generates UPI payment URI
  │  Browser renders QR code locally (qrcode npm package)
  │  Customer scans QR → pays in their UPI app
  ▼
Customer submits UTR / transaction reference
  │  UpiPaymentService::submitReference()
  │  Stores reference in payment.meta — does NOT mark paid
  ▼
Admin reviews order
  │  Checks bank/UPI app for matching amount + reference
  │  Ticks confirmation checkbox
  │  UpiPaymentService::verifyPayment()  →  marks paid
  ▼
Order proceeds through fulfilment
```

### Stock Reservation & Release

```
Order placed  ──→  InventoryService::adjust(-qty, 'checkout_reserve')
                     │  Locks variant row (FOR UPDATE)
                     │  Validates new stock ≥ 0
                     │  Writes inventory_movements record
                     ▼
                   Stock is reserved

If order cancelled (or unpaid expiry):
  ──→  InventoryService::adjust(+qty, 'order_cancel_release')
         │  Restores the exact reserved quantity
         │  Writes inverse movement record
         ▼
       Stock returned to pool

Manual admin adjustment:
  ──→  InventoryService::adjust(±qty, 'manual_adjustment')
         │  Admin provides a note (reason for change)
         ▼
       Full audit trail in inventory_movements
```

---

## 6. Authentication & Authorisation

### Authentication Methods

| Method | Implementation |
|---|---|
| Email + password | Custom controllers in `Auth/` (no Breeze/Fortify) |
| Google OAuth | Laravel Socialite via `GoogleSignInService` |
| Password reset | Standard Laravel reset flow with custom views |

### Authorisation Layers

```
Layer 1: Route middleware
  ├── 'auth' — must be logged in
  └── 'admin' — must have admin role + active account

Layer 2: Policies (model-level)
  ├── OrderPolicy — customers can only view/cancel their own orders
  └── UserAddressPolicy — customers can only edit/delete their own addresses

Layer 3: OrderAccess (session-based, for guests)
  └── Guest order visibility via session token granted at checkout

Layer 4: Gate definition
  └── 'access-admin' — user.is_active && user.isAdmin()
```

### Role Model

There are exactly two roles: `admin` and `customer`. The role comes from the `roles` table (joined via `users.role_id`). There is **no public admin registration route**. Admins are created only via:

- `php artisan arsh:create-admin`
- The database seeder (first run only, from `.env` values)

---

## 7. Payment Architecture

### Gateway Modes

```
┌──────────────┬─────────────────────────────────────────────────────┐
│  Mode        │  When                                               │
├──────────────┼─────────────────────────────────────────────────────┤
│  live        │  RAZORPAY_KEY_ID + RAZORPAY_KEY_SECRET are set      │
│  simulated   │  No keys, APP_ENV ≠ production                      │
│  disabled    │  No keys, APP_ENV = production                      │
└──────────────┴─────────────────────────────────────────────────────┘
```

The simulated gateway uses the same HMAC verification code path as the live gateway, signing with a key derived from `APP_KEY`. It cannot be enabled in production.

### Payment Security Rules

1. **Amount comes from the server**, never from the browser
2. **HMAC signature verification** on both checkout callbacks and webhooks
3. **Idempotent payment capture**: `payment_webhook_events` table with unique (provider, event_id) prevents reprocessing
4. **Browser redirect alone never marks an order paid** — only verified callbacks or webhooks do
5. **Unpaid order expiry**: stock released after 30 minutes via scheduled command

### Payment Methods Available

| Method | Flow | Verification |
|---|---|---|
| Cash on Delivery | Order created as paid immediately | Admin confirms delivery |
| Razorpay | Gateway modal → HMAC callback + webhook | Automated HMAC + amount check |
| UPI QR | Browser-generated QR → bank transfer | Manual admin verification |

---

## 8. Inventory & Stock Management

### Core Principle

**Every stock change** goes through `InventoryService::adjust()`. This guarantees:

1. The variant row is **locked** (`SELECT ... FOR UPDATE`)
2. The new stock level is **validated** (never negative)
3. An `inventory_movements` record is written (full audit trail)
4. Everything happens in a **single DB transaction**

### Movement Types

| Type | Direction | Trigger |
|---|---|---|
| `checkout_reserve` | Stock out (−) | Customer places order |
| `order_cancel_release` | Stock in (+) | Order cancelled or expired |
| `manual_adjustment` | Either | Admin adjusts stock in panel |
| `stocktake` | Either | Admin records counted stock |

### Deadlock Prevention

When checking out, variant rows are locked **ordered by `id`**. This prevents deadlocks when two customers buy overlapping sets of products concurrently.

---

## 9. Event System

```
OrderPlaced (event)
  └── SendOrderPlacedNotifications (listener, queued)
        ├── OrderPlacedNotification → customer email
        └── NewOrderAdminNotification → admin notification email
              (if notify_admin_new_order setting is enabled)

Status change (manual dispatch in OrderService::transition)
  └── OrderStatusChangedNotification → customer email
        (if notify_customer_status setting is enabled)
```

All notifications implement `ShouldQueue` and use `afterCommit()` to dispatch only after the DB transaction has committed. This prevents sending emails for orders that might be rolled back.

---

## 10. Frontend Architecture

### Asset Pipeline

```
Vite 7 (laravel-vite-plugin)
  ├── Entry: resources/css/app.css
  │     └── Imports: storefront.css, admin.css, upi.css, auth.css, service-pages.css
  ├── Entry: resources/js/app.js
  │     └── Alpine.js startup, storefront motion, theme helpers, lazy UPI QR import
  └── Entry: resources/js/admin.js
        └── Chart.js dashboards, admin-specific behaviour
```

### Blade Layout Hierarchy

```
layouts/app.blade.php          — Storefront shell (header, nav, footer)
layouts/home.blade.php         — Homepage (extended hero/features)
layouts/admin.blade.php        — Admin panel shell (sidebar, topbar)
```

### JavaScript Libraries

| Library | Usage | Notes |
|---|---|---|
| Alpine.js 3 | All interactive UI (dropdowns, modals, cart updates, filters) | No React/Vue |
| Chart.js 4 | Admin dashboard charts (revenue, orders, products) | Loaded only in admin layout |
| Framer Motion 14 | Storefront scroll animations | DOM API only, no React dependency |
| qrcode | UPI QR code rendering | Lazy-loaded when UPI checkout is shown |
| Axios | HTTP requests (AJAX cart operations, etc.) | Standard Laravel default |

### Blade Components

Reusable components in `resources/views/components/`:

| Component | Purpose |
|---|---|
| `product-card` | Product listing cards (shop, collections, home) |
| `collection-card` | Category/collection cards |
| `fragrance-sculpture` | Decorative SVG for the storefront |
| `google-sign-in` | Google OAuth button (disabled when not configured) |
| `admin-icon` | SVG icon helper for admin panel |
| `help-navigation` | Policy/help page navigation sidebar |
| `page-heading` | Consistent page titles |

---

## 11. Configuration Architecture

The application has two configuration layers:

### Layer 1: Static Config (`config/arsh.php` + `.env`)

Structural constants that require a code deployment to change:

- Order status definitions and allowed transitions
- Payment method identifiers
- Customer-cancellable statuses
- Fragrance families list
- Indian states list
- Unpaid order expiry window (30 minutes)
- Admin provisioning credentials (one-time use)

### Layer 2: Runtime Settings (`settings` table)

Business settings the owner changes in **Admin > Settings** without touching code:

- Business info (name, contact, address, GSTIN, hours)
- Social links (Instagram, Facebook, YouTube, WhatsApp)
- Commerce settings (tax, shipping, COD/online/UPI toggles)
- UPI payment details (payee name, UPI ID)
- Invoice formatting (prefix, footer, terms)
- Notification toggles
- Homepage section visibility
- Hero text

The `setting()` helper reads the `settings` table first, then falls back to defaults in `config/arsh.php`, then to the caller's `$default`. See [CONFIG_REFERENCE.md](CONFIG_REFERENCE.md) for the complete setting list.

---

## 12. Security Architecture

### Input Validation

- **Form Requests** (`CheckoutRequest`, `ProductRequest`) for complex validation
- **Inline validation** in controllers for simpler forms
- **Image uploads**: type + size validated, stored under random filenames, never served with original names

### Authorisation Enforcement

```
1. EnsureUserIsAdmin middleware → role checked on EVERY admin request
2. EnsureUserIsActive middleware → disabled accounts logged out immediately
3. Role and is_active fields → set only server-side, never from form input
4. OrderPolicy → IDOR protection on customer order views
5. UserAddressPolicy → IDOR protection on address management
6. OrderAccess → session-based guest order visibility
```

### Rate Limiting

Configured in `AppServiceProvider::configureRateLimiting()`:

| Limiter | Rate | Key |
|---|---|---|
| `login` | 5/min | email + IP |
| `oauth` | 10/min | IP |
| `register` | 5/min | IP |
| `password-reset` | 5/min | IP |
| `checkout` | 10/min | User ID or IP |
| `coupon` | 15/min | User ID or IP |
| `track` | 10/min | IP |
| `contact` | 5/min | IP |
| `newsletter` | 5/min | IP |
| `reviews` | 5/min | User ID or IP |

### Money Safety

All monetary calculations use `App\Support\Money` which works in **integer paise** (1/100 of a rupee). The `DECIMAL(12,2)` database columns are the storage format; the app converts at the boundary:

```
Browser input (string) → Money::toPaise() → integer maths → Money::fromPaise() → DB DECIMAL
```

This eliminates floating-point rounding errors across cart, checkout, order, and refund calculations.

### Admin Audit Trail

Every security-relevant admin action is logged via `ActivityLogger::log()` to the `admin_activity_logs` table, recording:

- Who (user ID)
- What (action, subject type + ID, description, properties)
- From where (IP address, user agent)
- When (created_at timestamp)

---

## 13. Error Handling

### Custom Exceptions

| Exception | Thrown by | Meaning |
|---|---|---|
| `CheckoutException` | `OrderService`, `InventoryService` | Stock unavailable, payment method disabled, or validation failure during checkout |
| `CouponException` | `CouponService` | Coupon invalid, expired, or usage limit reached |
| `GoogleSignInException` | `GoogleSignInService` | Google account not verified, email mismatch, or already linked elsewhere |

### Error Pages

Custom Blade templates in `resources/views/errors/` for: 403, 404, 419 (CSRF expired), 429 (rate limited), 500, 503 (maintenance).

---

## 14. Scheduled Tasks & Background Jobs

### Scheduler (runs every minute via cron)

| Command | Schedule | Purpose |
|---|---|---|
| `orders:expire-unpaid` | Every 10 minutes | Cancel unpaid online-payment orders, release reserved stock |
| `queue:prune-failed` | Daily | Remove failed job records older than 7 days |

### Queue

Queue connection: `database`. Notifications are dispatched with `ShouldQueue` + `afterCommit()`.

Three jobs run concurrently in development via `composer dev`:
1. `php artisan serve` — development server
2. `php artisan queue:listen` — job worker
3. `npm run dev` — Vite dev server

---

## 15. Design Decisions & Trade-offs

| Decision | Why | Trade-off |
|---|---|---|
| **Monolith over SPA** | Simpler deployment, better SEO, lower complexity for a small team | No real-time UI updates without page reload |
| **Services over fat models** | Business rules testable without HTTP; shared between controllers | Extra indirection layer; more files |
| **Integer paise for money** | No floating-point rounding errors ever | Must convert at DB/display boundaries |
| **Pessimistic locking** | Guarantees stock correctness under concurrency | Serialises concurrent checkouts for the same variant |
| **No Breeze/Fortify** | Full control over auth UI and flow | Must maintain auth controllers manually |
| **Alpine.js over Vue/React** | No build complexity for interactive elements; progressive enhancement | Limited component composition; no virtual DOM |
| **Simulated payment gateway** | Full checkout testing without Razorpay credentials | Extra code path to maintain; cannot run in production |
| **Snapshot order items** | Price changes don't alter historical orders | Data duplication (product name + price stored on order items) |
| **Session-based guest cart** | Guests can shop without accounts | Cart lost if session expires; merged on login |
| **No REST API** | Not needed — all rendering is server-side Blade | Mobile app would require building an API layer |

---

## Related Documentation

- **[README.md](../README.md)** — Setup, deployment, Razorpay configuration
- **[DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md)** — Complete technical reference (services, models, testing)
- **[DATABASE_REFERENCE.md](DATABASE_REFERENCE.md)** — Table schemas and relationships
- **[ROUTES_REFERENCE.md](ROUTES_REFERENCE.md)** — All routes with names and middleware
- **[CONFIG_REFERENCE.md](CONFIG_REFERENCE.md)** — Complete configuration reference
- **[SETTINGS_REFERENCE.md](SETTINGS_REFERENCE.md)** — Environment variables
- **[google-login-setup.md](google-login-setup.md)** — Google OAuth setup guide
