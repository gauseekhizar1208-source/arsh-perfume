# ARSH PERFUME project memory

Examined on 2026-10-24. This is a persistent project map for future work; recheck implementation and runtime state before making changes. No credentials or customer data are recorded here.

## Purpose and stack

India-focused e-commerce store for attars, roll-on perfumes and fragrance oils, with a server-rendered storefront and an administrator back office. Workspace: `C:\xampp\htdocs\arsh-perfume` (Windows/XAMPP, PowerShell).

- PHP ^8.2, Laravel ^12, MySQL-oriented migrations and an optional handwritten schema at `database/mysql/arsh_perfume.sql`.
- Blade, Tailwind CSS 4, Alpine.js 3, Vite 7. This is not a React SPA or Livewire application.
- Chart.js powers admin charts; Framer Motion DOM APIs power storefront motion without React; `qrcode` generates UPI QR codes locally in the browser.
- Laravel Socialite provides Google OAuth. Composer/npm lock files and installed dependency directories exist.

## Entry points and directory map

- `bootstrap/app.php`: Laravel boot, `/up` health endpoint, admin route registration, middleware aliases and Razorpay webhook CSRF exception.
- `routes/web.php`: catalogue, collections, cart, checkout, payments, tracking, content, contact, newsletter, SEO and authenticated customer account routes.
- `routes/auth.php`: custom email/password registration, login, resets, logout, Google redirect/callback and explicit account linking.
- `routes/admin.php`: `/admin`, route names prefixed `admin.`, requires web/auth/admin middleware.
- `app/Http/Controllers`: storefront/account controllers, plus `Auth/` and `Admin/` namespaces.
- `app/Http/Requests`: `CheckoutRequest`, `ProductRequest`.
- `app/Services`: business operations; keep order, payment and inventory rules here rather than duplicating them in controllers/views.
- `app/Models`: Eloquent entities, relations, catalogue scopes, settings and presentation helpers.
- `resources/views`: Blade layouts, pages, account, auth, components, admin, error pages and SEO sitemap.
- `resources/css/app.css`: Tailwind entry importing storefront, admin, UPI, auth and service-page styles.
- `resources/js/app.js`: Alpine startup, storefront motion, theme helpers and lazy UPI QR import. `admin.js` handles charts/admin behavior.
- `vite.config.js`: entries are app CSS, app JS and admin JS; ignores compiled Blade files in its watcher.
- `public/images/brand`: local branding and hero imagery. Product uploads use the configured media disk, locally `storage/app/public`, exposed through the public storage link.
- `design-system/arsh-perfume/MASTER.md` and `pages/admin.md`: design intent; verify actual CSS because older styling and documentation coexist.
- `docs/google-login-setup.md`: Google credential and callback configuration.
- `storage/framework`: generated Blade/cache files, browser profiles, screenshots and ad hoc browser-check scripts; avoid treating these as primary application source.

## Features and data

Storefront includes home, shop/filtering, collections, product variants, reviews, cart/coupons, guest or signed-in checkout, order tracking, wishlist, profile/password/address management and customer order history/cancellation. CMS includes policy/content pages, FAQs, banners, contact enquiries and newsletter subscriptions.

Admin includes dashboard, products/images/variants, categories/order, stock adjustments/movement history, orders/invoices/tracking/refunds, customers, coupons, content publishing, review moderation, enquiries, newsletters/exports, settings, reports and activity logs.

Entity groups:

- Accounts: roles, users, user addresses; Google identity/password-login fields are added by the October 2026 migration.
- Catalogue: categories, products, product variants, product images, reviews. Prices and stock belong to variants; products use soft deletes.
- Shopping: carts/items, wishlists/items, coupons, product/category coupon pivots and coupon usages.
- Fulfilment: orders, snapshotted order items, order status histories, inventory movements.
- Payments: payments and webhook events for replay/idempotency tracking.
- CMS/operations: pages, banners, FAQs, settings, enquiries, newsletter subscribers and admin activity logs, plus Laravel support tables.

## Business rules to preserve

- `Money` stores/calculates monetary amounts in integer paise and converts to decimal strings for persistence; `PricingService` shares cart/checkout/order totals, tax and shipping rules.
- `CartService`: persistent authenticated carts, session-token guest carts, merge on login, stock checks and maximum 10 units per line.
- `OrderService::place`: idempotency key, database transaction, stable-order variant row locks, revalidation of availability/current prices/stock, coupon lock/limits, historical item snapshots, immediate stock reservation, payment/history creation and cart clearing.
- `InventoryService`: stock adjustments and movement ledger; cancellation restores reserved stock once. Optional return restocking is supported.
- `config/arsh.php`: status labels, allowed transitions, customer cancellation states, payment methods, Indian states and runtime-setting defaults.
- Normal fulfilment is pending -> confirmed -> processing -> shipped -> out_for_delivery/delivered; cancellation/returns follow the configured transition map. Customers may cancel pending/confirmed orders only.
- Unpaid online orders cannot advance through fulfilment. COD becomes paid at delivery or via explicit admin collection.
- Razorpay uses server totals, signed callbacks/webhooks and duplicate-event handling. Local simulated mode exists; production requires real gateway configuration. `PaymentService` and `RazorpayService` own this flow.
- Direct UPI must be enabled with complete receiving-account settings. Payment metadata snapshots the receiving destination at checkout. Submitting a UTR/reference does not mark an order paid; admin must verify the bank receipt. Receipt references cannot settle two orders. UPI refunds record an externally performed transfer.
- `orders:expire-unpaid` releases expired unpaid Razorpay reservations after the configured 30-minute interval; direct UPI awaiting manual review is excluded.
- `OrderAccess` grants storefront access to the owning customer or browser session granted access; policies protect customer order/address actions. Tracking offers a separate order-number/email lookup.
- Admin requires an active admin role. Disabled users are signed out by web middleware. Public registration cannot assign admin rights. Provision admins using `arsh:create-admin` / `AdminProvisioner`.
- Google requires verified email and stable Google subject. On first Google sign-in, a case-insensitive verified email match automatically connects and signs into the existing active account, preserving its password and role. Already-linked identities use the stable Google subject; matching email cannot replace another linked identity. Optional explicit linking remains available. Public Google registration creates customers. Google-only accounts can establish password login.
- Runtime settings use the settings table and `setting()` helper; secret credentials belong in server environment configuration.
- Notifications cover placed orders and status changes; operate the queue worker for queued delivery.

## Design context

Documented storefront direction is midnight/amber luxury, brass accents, textured dark surfaces and native CSS 3D fragrance sculptures. Motion is progressive enhancement with reduced-motion support, keyboard/touch rotation controls and content visible without JS. Catalogue images and decorative brand illustration have distinct roles. Check actual CSS/font imports when adjusting typography: design notes and the app entry stylesheet contain different font references.

## Development and verification

- Run application: `php artisan serve`; assets: `npm run dev` or `npm run build`.
- Database setup: migrations/seeders; do not run destructive migrations or reseed the owner's live data merely to inspect the project.
- Product seed data is intentionally draft/incomplete; policy seeds are unpublished placeholders requiring owner confirmation.
- Storage: `php artisan storage:link`. Document root should be `public/`.
- Scheduler: `php artisan schedule:run`; unpaid-order expiry every ten minutes, failed-job pruning daily. Worker: `php artisan queue:work`.
- PHPUnit uses in-memory SQLite, array cache/session/mail and synchronous queues; `FeatureTestCase` uses RefreshDatabase and seeds roles.
- Tests cover money, access control, admin products/dashboard, checkout, inventory, order security, Razorpay webhooks, UPI, Google login and storefront rendering.
- **Observed 2026-10-24:** `php artisan test --compact`: **68 passed, 2 failed, 389 assertions**. Image generation test fails because CLI PHP GD is absent. Wrong-amount webhook test at `tests/Feature/PaymentWebhookTest.php:91` expects payment status `pending`, but implementation leaves `created`; order correctly stays unpaid according to the preceding assertion. Investigate the expected lifecycle before changing code/tests.
- No asset build, live gateway transfer, live OAuth flow or browser verification was performed during this examination.

## Known inconsistencies / future work

- README opening status and known-gap sections are stale: dependencies, lock files, product uploads and newer integrations already exist. Treat source/current checks as authoritative over old claims that nothing has executed.
- Admin product CSV import is routed but `ProductController` has no `import` method; CSV export exists. Do not assume import works based on route presence.
- Git status initially showed virtually all project source as untracked. Preserve existing work and avoid assuming a useful committed baseline exists.
- This examination mapped architecture and sampled implementation across the project; it is not an exhaustive security audit or production readiness certification.
