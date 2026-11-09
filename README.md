# ARSH PERFUME: Storefront and Admin (Laravel 12)

E-commerce site for ARSH PERFUME (attars, roll-on perfumes and fragrance oils). Stack: PHP 8.2+, Laravel 12, MySQL 8, Blade, Tailwind CSS (Vite), Alpine.js, Chart.js for the admin dashboard, Razorpay (plus Cash on Delivery).

> **Status:** the source was written without being executed. Composer and npm could not reach their registries in the build environment, so `composer install`, `npm run build` and `php artisan test` have **not** been run. A `php -l` syntax check passed on all 143 PHP files (Blade templates were not checked). Run the verification steps in section 3 before deploying, and fix anything they report.

---

## 1. Requirements

| Component | Version |
|---|---|
| PHP | 8.2 or newer (Laravel 12 minimum), with extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd` (image handling), `curl` |
| Composer | 2.x |
| Node.js | 20 LTS or newer (only to build assets) |
| MySQL | 8.0 |
| Web server | Apache (with `mod_rewrite`) or Nginx, document root set to `public/` |

---

## 2. Local setup

```bash
# 1. Get the code and install PHP dependencies
composer install

# 2. Environment file
cp .env.example .env
php artisan key:generate

# 3. Create the database (MySQL 8, utf8mb4)
mysql -u root -p -e "CREATE DATABASE arsh_perfume CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'arsh_app'@'localhost' IDENTIFIED BY 'CHOOSE-A-STRONG-PASSWORD'; GRANT ALL ON arsh_perfume.* TO 'arsh_app'@'localhost';"
```

Edit `.env`: set `DB_PASSWORD`, and optionally `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` (see section 4).

```bash
# 4. Tables, seed data and the first administrator
php artisan migrate --seed

# 5. Frontend assets
npm install
npm run build          # or `npm run dev` while developing

# 6. Public file storage for product images
php artisan storage:link

# 7. Run it
php artisan serve      # http://localhost:8000
```

### Creating or changing the administrator

The administrator is never created through the public site. There is no admin sign-up route.

```bash
php artisan arsh:create-admin you@example.com --name="Your Name"
```

The command prompts for a password. After the first run, **delete `ADMIN_NAME`, `ADMIN_EMAIL` and `ADMIN_PASSWORD` from `.env`**. They are read only by the seeder and the command.

### Seeded content

- **Categories** (`CategorySeeder`).
- **Products** (`ProductSeeder`): the owner's seven products, saved as **drafts** with stock 0. Products with no confirmed price (Arya, Tasleem, Labbek) have no price. Descriptions and fragrance notes are empty. Nothing is invented. Publish a product in the admin panel only after the owner has supplied the missing details.
- **Policy pages** (`PageSeeder`): Privacy, Terms, Refund, Shipping and similar pages are created **unpublished**, with `[OWNER TO CONFIRM]` placeholders. The owner must review and replace the text before publishing.

---

## 3. Verify the installation

Run these before going live. Nothing in this repository has been executed yet.

```bash
composer validate --strict
find . -path ./vendor -prune -o -name "*.php" -print0 | xargs -0 -n1 php -l
php artisan route:list
php artisan test                 # runs the Unit and Feature suites on in-memory SQLite
npm run build
```

Tests live in `tests/`:

- `Unit/MoneyTest.php`: integer-paise conversion, Indian digit grouping.
- `Feature/AccessControlTest.php`: login requirement, admin/customer separation.
- `Feature/AdminProductTest.php`: product creation, publishing gates, sale price rule, image validation, random file names.
- `Feature/CheckoutTest.php`: stock limits, COD order, price snapshot, stock changed before checkout, coupons, unsellable variants.
- `Feature/OrderSecurityTest.php`: customers cannot see or cancel other customers' orders, guest order visibility, no skipped status transitions, stock restored once.
- `Feature/InventoryTest.php`: stock adjustments write movements, stock never goes negative, stocktake records the difference.
- `Feature/PaymentWebhookTest.php`: bad signature rejected, single capture, duplicate webhooks ignored, wrong amount rejected, forged checkout signature refused.

A passing run is the only evidence that the code works. Do not describe the project as "tested" until `php artisan test` has passed on your machine.

---

## 4. Environment variables

| Variable | Purpose |
|---|---|
| `APP_ENV`, `APP_DEBUG`, `APP_URL` | Production: `APP_ENV=production`, `APP_DEBUG=false`, real `APP_URL` over HTTPS |
| `APP_KEY` | Created by `key:generate`. Keep it secret. Changing it logs everyone out and breaks encrypted data |
| `DB_*` | MySQL connection |
| `SESSION_SECURE_COOKIE` | `true` in production (HTTPS) |
| `QUEUE_CONNECTION` | `database` (run a worker, see section 6) |
| `MEDIA_DISK` | `public` locally, `s3` for cloud storage (fill in `AWS_*`) |
| `MAIL_*` | Mail server for order emails. `log` writes emails to the log file only |
| `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET` | Razorpay credentials, see section 5. Leave blank for the simulated gateway (non-production only) |
| `ADMIN_*` | First administrator only. Delete after use |
| `BCRYPT_ROUNDS` | 12 is a sensible production value |

Security rules for `.env`:

- `.env` is listed in `.gitignore`. Never commit it, and never commit real passwords, API keys or webhook secrets.
- Secret keys are never sent to the browser. Only `RAZORPAY_KEY_ID` is shared with the checkout page, as Razorpay requires.

---

## 5. Razorpay setup

1. Create a Razorpay account and switch the dashboard to **Test mode**.
2. Copy the Test API key ID (`rzp_test_...`) and secret into `.env` as `RAZORPAY_KEY_ID` and `RAZORPAY_KEY_SECRET`.
3. In Razorpay: **Webhooks → Add new webhook**.
   - URL: `https://YOUR-DOMAIN/webhooks/razorpay`
   - Secret: create one and put it in `.env` as `RAZORPAY_WEBHOOK_SECRET`.
   - Events: at least `payment.captured` and `payment.failed`.
4. Place a test order with a Razorpay test card and check that the order moves to *paid*.
5. Switch to live keys (`rzp_live_...`) only after step 4 works, and only after the business's Razorpay account is activated.

How payments are protected in this code:

- The amount is taken from the server's order total, never from the browser. It is compared with the amount Razorpay reports.
- Checkout callbacks are verified with an HMAC-SHA256 signature (order ID and payment ID, signed with the key secret). Webhooks are verified with an HMAC over the raw request body, using the webhook secret.
- Each webhook event is recorded in `payment_webhook_events` with a unique (provider, event ID) key, so retries and duplicates change nothing.
- An order is marked paid only after verification. A browser redirect alone never marks an order paid.
- Unpaid online orders are cancelled after 30 minutes and their stock is released (see section 6).

Simulated mode (no Razorpay keys, `APP_ENV` not `production`) uses the same verification code path with a locally derived signing key. It exists only for local development and cannot run in production.

---

## 6. Scheduler and queue worker

Add this single cron entry on the server (as the web user):

```cron
* * * * * cd /path/to/arsh-perfume && php artisan schedule:run >> /dev/null 2>&1
```

It runs `orders:expire-unpaid` every ten minutes and housekeeping jobs daily.

Order notifications are queued. Run a worker as a service so that it restarts on failure:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

On Linux, use Supervisor or a systemd unit to keep the worker running. After each deployment, run `php artisan queue:restart`.

---

## 7. Production deployment checklist

1. **Server:** PHP 8.2+, MySQL 8, HTTPS certificate (for example Let's Encrypt). Document root must be `public/`.
   - Nginx: `root /path/to/arsh-perfume/public;` with `try_files $uri /index.php?$query_string;`.
   - Apache: `DocumentRoot` set to `public/`, `AllowOverride All`, `mod_rewrite` enabled.
2. **Code:** deploy from version control or an archive. Do not copy your local `.env`. Create the production `.env` on the server, with `APP_DEBUG=false`.
3. **Install:** `composer install --no-dev --optimize-autoloader`, then `npm ci && npm run build`.
4. **Database:** `php artisan migrate --force`. Seed only on a fresh database, and remove `ADMIN_*` values afterwards.
5. **Optimise:** `php artisan config:cache route:cache view:cache event:cache`. Run `php artisan optimize:clear` before re-caching after any `.env` change.
6. **Permissions:** the web user must be able to write `storage/` and `bootstrap/cache/`. Nothing else should be writable.
7. **Storage link:** `php artisan storage:link`.
8. **Session cookie:** `SESSION_SECURE_COOKIE=true`.
9. **Cron and worker:** section 6.
10. **Razorpay:** test-mode checks first (section 5), then live keys.
11. **Policy pages:** the owner reviews and publishes them in the admin panel.
12. **Backups:** schedule daily MySQL dumps (`mysqldump --single-transaction arsh_perfume`) and back up `storage/app/public` (product images). Keep copies off the server, and test a restore.
13. **Logs:** rotate `storage/logs/`. Do not expose the `storage/` directory publicly.

---

## 8. Windows (Laragon or XAMPP)

**Laragon (recommended):**

1. Install Laragon Full, then start Apache and MySQL from the Laragon window.
2. Use Laragon's *Terminal* so that `php`, `composer` and `npm` are on the path.
3. Clone or copy the project to `C:\laragon\www\arsh-perfume`. Laragon serves it at `http://arsh-perfume.test`, with `public/` as the document root.
4. Create the database `arsh_perfume` with phpMyAdmin (utf8mb4).
5. Follow section 2 from step 1. Use `DB_USERNAME=root` only on a local machine; create a dedicated user otherwise.

**XAMPP:**

1. Use PHP 8.2 or newer (XAMPP's bundled PHP must be upgraded or replaced). Enable `pdo_mysql`, `fileinfo`, `gd`, `openssl` and `mbstring` in `php.ini`.
2. Place the project in `C:\xampp\htdocs\arsh-perfume` and set `DocumentRoot` to its `public` folder in the Apache virtual host configuration.
3. Run the remaining steps from a terminal inside the project folder.

If `storage:link` fails on Windows, run the terminal as administrator, or enable developer mode.

---

## 9. Database overview

A hand-written MySQL 8 schema is in `database/mysql/arsh_perfume.sql`. It was not executed against a server. Use `php artisan migrate` as the main route. Import the SQL file only if you prefer the `mysql` client, and then run `php artisan db:seed`.

The application uses 29 tables. Laravel's own support tables (`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`) are included in the migrations as well.

| Area | Tables | Notes |
|---|---|---|
| Accounts | `users`, `roles`, `user_addresses` | `users.role_id` links to `roles`. Role and active status are set by the server, never by form input |
| Catalogue | `categories`, `products`, `product_variants`, `product_images`, `reviews` | A product has many variants (sizes). Prices and stock live on variants. Products use soft deletes |
| Inventory | `inventory_movements` | Every stock change is logged with a type and quantity |
| Cart and wishlist | `carts`, `cart_items`, `wishlists`, `wishlist_items` | Guests use a session token; carts are merged on login |
| Coupons | `coupons`, `coupon_categories`, `coupon_products`, `coupon_usages` | Pivot tables limit a coupon to categories or products |
| Orders | `orders`, `order_items`, `order_status_histories` | `order_items` snapshot price and product name at purchase time. Status changes are recorded |
| Payments | `payments`, `payment_webhook_events` | One row per gateway payment attempt. Webhook events are recorded for idempotency |
| Content and admin | `pages`, `banners`, `faqs`, `settings`, `contact_enquiries`, `newsletter_subscribers`, `admin_activity_logs` | CMS content, site settings, enquiries, subscribers and an audit log of admin actions |

Money is stored as `DECIMAL` and calculated in integer paise in PHP (`App\Support\Money`), which avoids floating-point rounding errors.

---

## 10. Known gaps

- The code has not been executed (see the status note at the top). Expect to fix small errors on the first run.
- The live Razorpay path has not been tested. Test it only with Razorpay test keys before going live.
- No product photos are included. Upload them through the admin panel.
- No `composer.lock` or `package-lock.json` is included. Commit the lock files after the first successful install so that every deployment uses the same versions.
- The admin category ordering is a plain number field, not drag and drop.
- CSV product export exists; CSV import does not.
- The brief's table count (25) should be checked against the owner's list: the schema has 29 application tables.
- Breeze and Livewire are not used. Authentication is built with Laravel's own controllers, and interactivity uses Alpine.js.

---

## 11. Security notes for the owner

- Customers cannot view or cancel another customer's order. Guest order pages are visible only in the browser session that placed the order.
- Role and active flags cannot be set through any public form.
- Admin pages require the `admin` role, checked on every request.
- Uploaded images are validated by type and size, and stored under random names.
- Product publishing requires a price on an active variant.
- Only the owner should decide what the policy pages say.

## Direct UPI QR payments

Configure the payee name and UPI ID under **Admin > Settings > UPI payments**, then enable direct UPI payments. The checkout option is available only when these settings are complete. Existing orders retain the receiving account that was configured when the order was placed.

Customers select **Pay by UPI QR**, scan the order-specific QR (or save it for scanning on the same phone), and submit their transaction reference / UTR. QR generation runs locally in the browser through the bundled `qrcode` dependency; payment data is not sent to a QR-image service.

Open **Admin > Orders > the order**. Check the actual bank/UPI receipt for the full amount and reference, tick the receipt confirmation, and select **Verify UPI payment received**. The order can then be confirmed and fulfilled. A customer reference alone never marks an order paid, and a verified bank reference cannot settle a second order.

Direct UPI orders awaiting manual review do not use the Razorpay unpaid-order timeout. Cancel unpaid orders from admin when appropriate; cancellation restores reserved stock. UPI refunds must be transferred separately through the bank/UPI app before using **Record refund**, which only updates the store's records.

Validation for this feature: production asset build; 34 targeted tests covering checkout, UPI verification, access controls, inventory reservation/cancellation, refunds, and existing storefront/admin rendering; browser checks for QR rendering, PNG download and mobile layout. No real bank transfer is performed by these checks.

## Google account sign-in

Google login is implemented using Laravel Socialite. Add your Google Web application OAuth Client ID and Client Secret to `.env`, and register the exact callback URL. See [Google login setup](docs/google-login-setup.md) for the complete Google Cloud and local/production configuration steps. Until configured, the Google button is disabled and ordinary email login remains available. A verified matching Google email automatically connects and signs into an existing active account while preserving its password, data and role. Disabled accounts and conflicting linked identities remain blocked; public Google registration always creates customer accounts.

---

## 12. Documentation index

| Document | What it covers |
|---|---|
| **README.md** *(this file)* | Setup, deployment, Razorpay, UPI, scheduling, Windows |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | System design, request lifecycle, data flow diagrams, security architecture |
| [docs/CONFIG_REFERENCE.md](docs/CONFIG_REFERENCE.md) | All configuration values: `config/arsh.php`, runtime settings table, `.env` variables |
| [docs/DEVELOPER_GUIDE.md](docs/DEVELOPER_GUIDE.md) | Complete technical reference (services, models, auth, payments, testing, deployment) |
| [docs/DATABASE_REFERENCE.md](docs/DATABASE_REFERENCE.md) | Table schemas, columns, and relationships |
| [docs/ROUTES_REFERENCE.md](docs/ROUTES_REFERENCE.md) | All routes listed with names and middleware |
| [docs/SETTINGS_REFERENCE.md](docs/SETTINGS_REFERENCE.md) | Quick `.env` variable reference |
| [docs/google-login-setup.md](docs/google-login-setup.md) | Google OAuth credential and callback configuration |
