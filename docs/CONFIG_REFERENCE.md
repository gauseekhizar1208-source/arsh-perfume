# ARSH PERFUME — Configuration Reference

> Complete reference for all configuration values in the application.

---

## Table of Contents

1. [Configuration Layers](#1-configuration-layers)
2. [Static Configuration — config/arsh.php](#2-static-configuration--configarshphp)
3. [Runtime Settings — settings table](#3-runtime-settings--settings-table)
4. [Environment Variables — .env](#4-environment-variables--env)
5. [Laravel Config Files](#5-laravel-config-files)
6. [Helper Functions](#6-helper-functions)

---

## 1. Configuration Layers

The application uses three configuration layers, each serving a different purpose:

| Layer | File / Table | Changed by | Requires restart |
|---|---|---|---|
| **Environment** | `.env` | Developer / server admin | Yes (`config:cache` or restart) |
| **Static config** | `config/arsh.php` | Developer (code change) | Yes (deployment) |
| **Runtime settings** | `settings` table | Store owner (Admin > Settings) | No (instant) |

**Lookup order** for the `setting()` helper:

```
settings table → config/arsh.php defaults → caller's $default → null
```

---

## 2. Static Configuration — config/arsh.php

These values define the structural shape of the application. They are not editable from the admin panel.

### Brand

| Key | Type | Default | Description |
|---|---|---|---|
| `arsh.brand` | string | `'ARSH PERFUME'` | Brand name used in code references |
| `arsh.tagline` | string | `'Feel the Best Quality.'` | Default tagline (overridable in runtime settings) |

### Media

| Key | Type | Default | Env var | Description |
|---|---|---|---|---|
| `arsh.media_disk` | string | `'public'` | `MEDIA_DISK` | Filesystem disk for product/banner images. `'public'` for local storage, `'s3'` for cloud |

### Order Lifecycle

#### Statuses

```php
'order_statuses' => [
    'pending'          => 'Pending',
    'confirmed'        => 'Confirmed',
    'processing'       => 'Processing',
    'shipped'          => 'Shipped',
    'out_for_delivery'  => 'Out for Delivery',
    'delivered'        => 'Delivered',
    'cancelled'        => 'Cancelled',
    'returned'         => 'Returned',
]
```

Used by `OrderService::transition()` to validate status labels and render human-readable names.

#### Status Transitions

Defines which forward moves are allowed. Any transition not listed here is **rejected**.

```
pending ──────→ confirmed, cancelled
confirmed ────→ processing, cancelled
processing ───→ shipped, cancelled
shipped ──────→ out_for_delivery, delivered, returned
out_for_delivery → delivered, returned
delivered ────→ returned
cancelled ────→ (terminal)
returned ─────→ (terminal)
```

```
                ┌──────────────────────────────────────────────┐
                │                                              │
  pending ──→ confirmed ──→ processing ──→ shipped ──→ out_for_delivery
    │            │               │            │              │
    │            │               │            ├──→ delivered ←┘
    │            │               │            │       │
    └──→ cancelled ←─────────────┘            └──→ returned ←─┘
```

#### Customer-Cancellable Statuses

```php
'customer_cancellable_statuses' => ['pending', 'confirmed']
```

Customers can cancel their own order only while it is in one of these statuses. Once an order moves to `processing` or beyond, only an admin can cancel it.

### Payment

#### Payment Statuses

| Key | Label |
|---|---|
| `pending` | Pending |
| `paid` | Paid |
| `failed` | Failed |
| `refunded` | Refunded |

#### Payment Methods

| Key | Label | Notes |
|---|---|---|
| `cod` | Cash on Delivery | Toggled by `cod_enabled` runtime setting |
| `razorpay` | Online Payment (Razorpay) | Requires `RAZORPAY_KEY_ID` + `RAZORPAY_KEY_SECRET` in `.env` |
| `upi` | UPI QR (manual verification) | Toggled by `upi_enabled` runtime setting + configured payee details |

### Unpaid Order Expiry

```php
'unpaid_order_expiry_minutes' => 30
```

Online payment orders (Razorpay) that remain unpaid for this many minutes are automatically cancelled by `orders:expire-unpaid`, and their reserved stock is released. UPI orders are **not** subject to this timer — they require manual admin cancellation.

### Admin Provisioning

```php
'admin' => [
    'name'     => env('ADMIN_NAME'),
    'email'    => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),
]
```

Read **only** by the database seeder and the `arsh:create-admin` command. Remove these values from `.env` after the first admin account is created.

### Fragrance Families

```php
'fragrance_families' => [
    'Floral', 'Woody', 'Musk', 'Oud', 'Fresh & Fruity',
    'Spicy', 'Amber', 'Aquatic', 'Gourmand',
]
```

Used for product categorisation and filtering in the shop.

### Indian States

A complete list of 36 Indian states and union territories, used for address forms (checkout and account management).

---

## 3. Runtime Settings — settings table

These are editable from **Admin > Settings** in the browser. The `setting()` helper reads the database value, falling back to the defaults below.

### Business (`business` group)

| Key | Type | Default | Description |
|---|---|---|---|
| `business_name` | string | `'Arsh Perfume'` | Store name shown in header, emails, invoices |
| `tagline` | string | `'Feel the Best Quality.'` | Displayed below the brand name |
| `contact_email` | string | `''` | Shown on contact page, in emails |
| `contact_phone` | string | `''` | Shown on contact page and footer |
| `whatsapp_number` | string | `''` | WhatsApp link in footer/contact |
| `business_address` | text | `''` | Full address shown in invoices and contact page |
| `business_hours` | string | `''` | Operating hours text |
| `gstin` | string | `''` | GST Identification Number (shown on invoices if set) |
| `logo_path` | string | `''` | Path to uploaded logo image |
| `favicon_path` | string | `''` | Path to uploaded favicon |
| `footer_text` | text | `''` | Custom text in the site footer |
| `about_snippet` | text | `''` | Short about text for the homepage |
| `footer_links` | text | `''` | Additional footer links, one `Label\|URL` per line |

### Social (`social` group)

| Key | Type | Default | Description |
|---|---|---|---|
| `social_instagram` | string | `''` | Instagram profile URL |
| `social_facebook` | string | `''` | Facebook page URL |
| `social_youtube` | string | `''` | YouTube channel URL |
| `social_whatsapp` | string | `''` | WhatsApp link URL |

### Commerce (`commerce` group)

| Key | Type | Default | Description |
|---|---|---|---|
| `currency_symbol` | string | `'₹'` | Currency symbol used in price display |
| `tax_enabled` | bool | `false` | Whether to calculate tax |
| `tax_rate` | decimal | `0` | Tax percentage (e.g., `18` for 18% GST) |
| `prices_include_tax` | bool | `true` | If true, tax is extracted from prices (inclusive); if false, tax is added on top |
| `shipping_flat_rate` | decimal | `0` | Flat shipping fee in rupees (e.g., `50.00`) |
| `free_shipping_threshold` | decimal | `0` | Order subtotal above which shipping is free (0 = no free shipping) |
| `cod_enabled` | bool | `true` | Whether Cash on Delivery is available at checkout |
| `online_payment_enabled` | bool | `true` | Whether Razorpay online payment is offered |
| `guest_checkout_enabled` | bool | `true` | Whether guests can check out without creating an account |
| `default_low_stock_threshold` | int | `5` | Stock level at which a product is flagged as low stock in admin |

### UPI Payments (`upi_payments` group)

| Key | Type | Default | Description |
|---|---|---|---|
| `upi_enabled` | bool | `false` | Whether UPI QR payment is available at checkout |
| `upi_payee_name` | string | `''` | Payee name shown on the UPI QR code |
| `upi_id` | string | `''` | UPI VPA (e.g., `name@bank`). Must be a valid format for the UPI option to appear |

> **Note:** UPI checkout is available only when `upi_enabled` is true, `upi_id` matches a valid UPI address format, and `upi_payee_name` is filled. All three conditions must be met.

### Invoice (`invoice` group)

| Key | Type | Default | Description |
|---|---|---|---|
| `invoice_prefix` | string | `'INV-'` | Prefix for invoice numbers |
| `invoice_footer` | text | `'Thank you for shopping with Arsh Perfume.'` | Text at the bottom of invoices |
| `invoice_terms` | text | `''` | Terms and conditions printed on invoices |

### Notifications (`notifications` group)

| Key | Type | Default | Description |
|---|---|---|---|
| `notify_admin_new_order` | bool | `true` | Email admin when a new order is placed |
| `notify_customer_status` | bool | `true` | Email customer when their order status changes |
| `admin_notification_email` | string | `''` | Email address that receives admin notifications |

### Homepage (`homepage` group)

These control which sections are visible on the storefront homepage:

| Key | Type | Default | Description |
|---|---|---|---|
| `home_show_featured` | bool | `true` | Show featured products section |
| `home_show_bestsellers` | bool | `true` | Show bestsellers section |
| `home_show_new_arrivals` | bool | `true` | Show new arrivals section |
| `home_show_categories` | bool | `true` | Show category cards section |
| `home_show_why_choose` | bool | `true` | Show "Why Choose Us" section |
| `home_show_reviews` | bool | `true` | Show customer reviews section |
| `home_show_newsletter` | bool | `true` | Show newsletter signup section |
| `home_show_social` | bool | `true` | Show social media section |
| `hero_heading` | string | `'Feel the Best Quality.'` | Hero banner main heading |
| `hero_subheading` | text | `'Attars, roll-ons and fragrance oils crafted to be remembered.'` | Hero banner subtitle |

---

## 4. Environment Variables — .env

### Application

| Variable | Production value | Description |
|---|---|---|
| `APP_NAME` | `"Arsh Perfume"` | Application name |
| `APP_ENV` | `production` | Environment (local/production) |
| `APP_KEY` | (generated) | Encryption key — never share or change after deployment |
| `APP_DEBUG` | `false` | **Must** be false in production |
| `APP_URL` | `https://your-domain.com` | Full base URL (HTTPS in production) |
| `APP_LOCALE` | `en` | Default language |
| `APP_FAKER_LOCALE` | `en_IN` | Faker locale for test data |

### Database

| Variable | Default | Description |
|---|---|---|
| `DB_CONNECTION` | `mysql` | Database driver |
| `DB_HOST` | `127.0.0.1` | MySQL host |
| `DB_PORT` | `3306` | MySQL port |
| `DB_DATABASE` | `arsh_perfume` | Database name |
| `DB_USERNAME` | `arsh_app` | Database user |
| `DB_PASSWORD` | (empty) | Database password |

### Session / Cache / Queue

| Variable | Default | Description |
|---|---|---|
| `SESSION_DRIVER` | `database` | Session storage |
| `SESSION_LIFETIME` | `120` | Session lifetime in minutes |
| `SESSION_SECURE_COOKIE` | `false` | Set `true` in production (HTTPS) |
| `CACHE_STORE` | `database` | Cache backend |
| `QUEUE_CONNECTION` | `database` | Queue backend |

### Media Storage

| Variable | Default | Description |
|---|---|---|
| `MEDIA_DISK` | `public` | `public` for local, `s3` for cloud |
| `FILESYSTEM_DISK` | `local` | Default filesystem disk |
| `AWS_ACCESS_KEY_ID` | (empty) | S3 credentials (if `MEDIA_DISK=s3`) |
| `AWS_SECRET_ACCESS_KEY` | (empty) | S3 credentials |
| `AWS_DEFAULT_REGION` | `ap-south-1` | S3 region |
| `AWS_BUCKET` | (empty) | S3 bucket name |

### Mail

| Variable | Default | Description |
|---|---|---|
| `MAIL_MAILER` | `log` | `log` in dev (writes to log file only), SMTP/SES in production |
| `MAIL_HOST` | `127.0.0.1` | SMTP host |
| `MAIL_PORT` | `2525` | SMTP port |
| `MAIL_USERNAME` | (empty) | SMTP user |
| `MAIL_PASSWORD` | (empty) | SMTP password |
| `MAIL_FROM_ADDRESS` | `orders@example.com` | Sender email |
| `MAIL_FROM_NAME` | `${APP_NAME}` | Sender display name |

### Razorpay

| Variable | Description |
|---|---|
| `RAZORPAY_KEY_ID` | API Key ID (`rzp_test_*` or `rzp_live_*`). Shared with browser on payment page |
| `RAZORPAY_KEY_SECRET` | API Key Secret. **Never** sent to the browser |
| `RAZORPAY_WEBHOOK_SECRET` | Webhook signing secret configured in Razorpay dashboard |

> **Simulated mode:** Leave all three blank with `APP_ENV=local` to use the built-in simulated gateway for development. The simulator cannot run in production.

### Google OAuth

| Variable | Description |
|---|---|
| `GOOGLE_CLIENT_ID` | Google OAuth Web application Client ID |
| `GOOGLE_CLIENT_SECRET` | Google OAuth Client Secret |
| `GOOGLE_REDIRECT_URI` | Callback URL (defaults to `APP_URL/auth/google/callback` if unset) |

> See [google-login-setup.md](google-login-setup.md) for complete Google Cloud Console setup steps.

### Admin (one-time use)

| Variable | Description |
|---|---|
| `ADMIN_NAME` | First admin display name |
| `ADMIN_EMAIL` | First admin email |
| `ADMIN_PASSWORD` | First admin password |

> **Delete these from `.env` after running `php artisan migrate --seed` or `arsh:create-admin`.**

### Security

| Variable | Default | Description |
|---|---|---|
| `BCRYPT_ROUNDS` | `12` | Password hashing cost. 12 is recommended for production |

---

## 5. Laravel Config Files

Other config files in `config/` and their purpose:

| File | Key Settings |
|---|---|
| `config/app.php` | Application name, locale, timezone |
| `config/auth.php` | Authentication guards and providers (Eloquent User model) |
| `config/cache.php` | Cache store configuration |
| `config/database.php` | Database connections (MySQL primary) |
| `config/filesystems.php` | Disk definitions (local, public, s3) |
| `config/hashing.php` | Bcrypt configuration |
| `config/logging.php` | Log channels (stack: daily) |
| `config/mail.php` | Mail transport configuration |
| `config/queue.php` | Queue connections (database) |
| `config/services.php` | Third-party services (Razorpay credentials, Google OAuth) |
| `config/session.php` | Session driver and lifetime |
| `config/view.php` | View compiled path |

---

## 6. Helper Functions

The following global helpers are registered via `app/Support/helpers.php` (autoloaded in `composer.json`):

### `setting(string $key, mixed $default = null): mixed`

Read a runtime setting. Checks the `settings` table first, then falls back to `config/arsh.php` defaults, then `$default`.

```php
setting('currency_symbol');              // '₹'
setting('tax_rate', '0');                // '18' if set in admin, '0' otherwise
setting('cod_enabled', true);            // true/false
```

### `money(string|int|float|null $amount): string`

Format a decimal amount as rupees with Indian digit grouping:

```php
money('1234.50');    // '₹1,234.50'
money('123456');     // '₹1,23,456'
money(0);            // '₹0'
```

### `media_url(?string $path, ?string $fallback = null): string`

Public URL for a stored media file. Handles local storage, S3, absolute URLs, and fallback to a placeholder SVG.

```php
media_url('products/abc123.jpg');        // '/storage/products/abc123.jpg' (local)
media_url(null);                         // '/images/placeholder.svg'
media_url('https://cdn.example.com/x');  // 'https://cdn.example.com/x' (passthrough)
```

---

## Related Documentation

- **[README.md](../README.md)** — Setup, deployment, Razorpay configuration
- **[ARCHITECTURE.md](ARCHITECTURE.md)** — System design and component interaction
- **[DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md)** — Complete technical reference
- **[DATABASE_REFERENCE.md](DATABASE_REFERENCE.md)** — Table schemas and relationships
- **[SETTINGS_REFERENCE.md](SETTINGS_REFERENCE.md)** — Quick environment variable reference
