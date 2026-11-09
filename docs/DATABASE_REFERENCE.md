# Database reference

[Documentation home](DOCUMENTATION.md)

Generated from migration source on 2026-10-27. This reference includes **36 explicitly declared tables: 29 application tables and 7 framework support tables**. Laravel also creates its `migrations` bookkeeping table. This describes the source schema, not the contents or migration status of an installed database.

Migrations are authoritative. The optional handwritten SQL file predates the Google-login migration and must not replace the current migration workflow. Definitions below use Laravel Blueprint notation so precision, limits, nullability, defaults, indexes and foreign-key deletion rules remain explicit.

`id()` declares an auto-incrementing primary key; `timestamps()` adds nullable creation/update timestamps; `softDeletes()` adds a deletion timestamp; `rememberToken()` adds a nullable remember-me token. A column is required unless `nullable()` is present. `constrained()` infers the referenced table when no table name is supplied. Composite indexes are listed below each table; inline indexes appear in the column declaration.

Currency amounts use decimal database columns. Checkout arithmetic uses integer paise through `Money`; reporting aggregates use database sums. Price and stock belong to product variants. Order items and addresses are snapshots. JSON columns store product specifications, payment metadata and webhook payloads; they are not card-storage fields.

See the handbook for domain relationships, order/payment states, inventory rules and seed behavior. Regenerate with `php docs/tools/generate-reference.php`.

## Tables

- [roles](#roles)
- [users](#users)
- [password_reset_tokens](#password-reset-tokens)
- [sessions](#sessions)
- [cache](#cache)
- [cache_locks](#cache-locks)
- [jobs](#jobs)
- [job_batches](#job-batches)
- [failed_jobs](#failed-jobs)
- [user_addresses](#user-addresses)
- [categories](#categories)
- [products](#products)
- [product_images](#product-images)
- [product_variants](#product-variants)
- [carts](#carts)
- [cart_items](#cart-items)
- [wishlists](#wishlists)
- [wishlist_items](#wishlist-items)
- [coupons](#coupons)
- [coupon_products](#coupon-products)
- [coupon_categories](#coupon-categories)
- [orders](#orders)
- [order_items](#order-items)
- [payments](#payments)
- [payment_webhook_events](#payment-webhook-events)
- [coupon_usages](#coupon-usages)
- [order_status_histories](#order-status-histories)
- [inventory_movements](#inventory-movements)
- [reviews](#reviews)
- [contact_enquiries](#contact-enquiries)
- [newsletter_subscribers](#newsletter-subscribers)
- [banners](#banners)
- [pages](#pages)
- [faqs](#faqs)
- [settings](#settings)
- [admin_activity_logs](#admin-activity-logs)

<a id="roles"></a>
## roles

Source: [2026_01_01_000001_create_roles_table.php](../database/migrations/2026_01_01_000001_create_roles_table.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `name` | `string('name', 40)->unique()` |
| `label` | `string('label', 80)` |
| `created_at, updated_at` | `timestamps()` |

<a id="users"></a>
## users

Source: [2026_01_01_000002_create_users_table.php](../database/migrations/2026_01_01_000002_create_users_table.php), [2026_10_22_000012_add_google_login_to_users.php](../database/migrations/2026_10_22_000012_add_google_login_to_users.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `name` | `string('name')` |
| `email` | `string('email')->unique()` |
| `phone` | `string('phone', 20)->nullable()` |
| `email_verified_at` | `timestamp('email_verified_at')->nullable()` |
| `password` | `string('password')` |
| `role_id` | `foreignId('role_id')->constrained('roles')->restrictOnDelete()` |
| `is_active` | `boolean('is_active')->default(true)` |
| `last_login_at` | `timestamp('last_login_at')->nullable()` |
| `remember_token` | `rememberToken()` |
| `created_at, updated_at` | `timestamps()` |
| `google_id` | `string('google_id')->nullable()->unique()` |
| `password_login_enabled` | `boolean('password_login_enabled')->default(true)` |

Additional indexes/constraints: `index(['role_id', 'is_active'])`.

<a id="password-reset-tokens"></a>
## password_reset_tokens

Source: [2026_01_01_000002_create_users_table.php](../database/migrations/2026_01_01_000002_create_users_table.php).

| Column(s) | Schema declaration |
|---|---|
| `email` | `string('email')->primary()` |
| `token` | `string('token')` |
| `created_at` | `timestamp('created_at')->nullable()` |

<a id="sessions"></a>
## sessions

Source: [2026_01_01_000002_create_users_table.php](../database/migrations/2026_01_01_000002_create_users_table.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `string('id')->primary()` |
| `user_id` | `foreignId('user_id')->nullable()->index()` |
| `ip_address` | `string('ip_address', 45)->nullable()` |
| `user_agent` | `text('user_agent')->nullable()` |
| `payload` | `longText('payload')` |
| `last_activity` | `integer('last_activity')->index()` |

<a id="cache"></a>
## cache

Source: [2026_01_01_000003_create_cache_and_jobs_tables.php](../database/migrations/2026_01_01_000003_create_cache_and_jobs_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `key` | `string('key')->primary()` |
| `value` | `mediumText('value')` |
| `expiration` | `integer('expiration')` |

<a id="cache-locks"></a>
## cache_locks

Source: [2026_01_01_000003_create_cache_and_jobs_tables.php](../database/migrations/2026_01_01_000003_create_cache_and_jobs_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `key` | `string('key')->primary()` |
| `owner` | `string('owner')` |
| `expiration` | `integer('expiration')` |

<a id="jobs"></a>
## jobs

Source: [2026_01_01_000003_create_cache_and_jobs_tables.php](../database/migrations/2026_01_01_000003_create_cache_and_jobs_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `queue` | `string('queue')->index()` |
| `payload` | `longText('payload')` |
| `attempts` | `unsignedTinyInteger('attempts')` |
| `reserved_at` | `unsignedInteger('reserved_at')->nullable()` |
| `available_at` | `unsignedInteger('available_at')` |
| `created_at` | `unsignedInteger('created_at')` |

<a id="job-batches"></a>
## job_batches

Source: [2026_01_01_000003_create_cache_and_jobs_tables.php](../database/migrations/2026_01_01_000003_create_cache_and_jobs_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `string('id')->primary()` |
| `name` | `string('name')` |
| `total_jobs` | `integer('total_jobs')` |
| `pending_jobs` | `integer('pending_jobs')` |
| `failed_jobs` | `integer('failed_jobs')` |
| `failed_job_ids` | `longText('failed_job_ids')` |
| `options` | `mediumText('options')->nullable()` |
| `cancelled_at` | `integer('cancelled_at')->nullable()` |
| `created_at` | `integer('created_at')` |
| `finished_at` | `integer('finished_at')->nullable()` |

<a id="failed-jobs"></a>
## failed_jobs

Source: [2026_01_01_000003_create_cache_and_jobs_tables.php](../database/migrations/2026_01_01_000003_create_cache_and_jobs_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `uuid` | `string('uuid')->unique()` |
| `connection` | `text('connection')` |
| `queue` | `text('queue')` |
| `payload` | `longText('payload')` |
| `exception` | `longText('exception')` |
| `failed_at` | `timestamp('failed_at')->useCurrent()` |

<a id="user-addresses"></a>
## user_addresses

Source: [2026_01_01_000004_create_user_addresses_table.php](../database/migrations/2026_01_01_000004_create_user_addresses_table.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `user_id` | `foreignId('user_id')->constrained()->cascadeOnDelete()` |
| `label` | `string('label', 40)->nullable()` |
| `name` | `string('name')` |
| `phone` | `string('phone', 20)` |
| `address_line1` | `string('address_line1')` |
| `address_line2` | `string('address_line2')->nullable()` |
| `city` | `string('city', 100)` |
| `state` | `string('state', 100)` |
| `postal_code` | `string('postal_code', 10)` |
| `country` | `string('country', 60)->default('India')` |
| `is_default` | `boolean('is_default')->default(false)` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `index(['user_id', 'is_default'])`.

<a id="categories"></a>
## categories

Source: [2026_01_01_000005_create_catalog_tables.php](../database/migrations/2026_01_01_000005_create_catalog_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `name` | `string('name')` |
| `slug` | `string('slug')->unique()` |
| `description` | `text('description')->nullable()` |
| `image_path` | `string('image_path')->nullable()` |
| `sort_order` | `unsignedInteger('sort_order')->default(0)` |
| `is_active` | `boolean('is_active')->default(true)` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `index(['is_active', 'sort_order'])`.

<a id="products"></a>
## products

Source: [2026_01_01_000005_create_catalog_tables.php](../database/migrations/2026_01_01_000005_create_catalog_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `category_id` | `foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete()` |
| `sku` | `string('sku', 64)->unique()` |
| `name` | `string('name')` |
| `slug` | `string('slug')->unique()` |
| `short_description` | `string('short_description', 300)->nullable()` |
| `description` | `longText('description')->nullable()` |
| `fragrance_family` | `string('fragrance_family', 60)->nullable()->index()` |
| `top_notes` | `text('top_notes')->nullable()` |
| `heart_notes` | `text('heart_notes')->nullable()` |
| `base_notes` | `text('base_notes')->nullable()` |
| `longevity` | `string('longevity', 120)->nullable()` |
| `projection` | `string('projection', 120)->nullable()` |
| `specifications` | `json('specifications')->nullable()` |
| `is_featured` | `boolean('is_featured')->default(false)` |
| `is_bestseller` | `boolean('is_bestseller')->default(false)` |
| `status` | `string('status', 16)->default('draft')` |
| `meta_title` | `string('meta_title')->nullable()` |
| `meta_description` | `string('meta_description', 320)->nullable()` |
| `owner_notes` | `text('owner_notes')->nullable()` |
| `sales_count` | `unsignedInteger('sales_count')->default(0)` |
| `deleted_at` | `softDeletes()` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `index(['status', 'is_featured'])`; `index(['status', 'is_bestseller'])`; `index(['status', 'created_at'])`; `index('name')`; `index('sales_count')`.

<a id="product-images"></a>
## product_images

Source: [2026_01_01_000005_create_catalog_tables.php](../database/migrations/2026_01_01_000005_create_catalog_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `product_id` | `foreignId('product_id')->constrained()->cascadeOnDelete()` |
| `path` | `string('path')` |
| `alt` | `string('alt')->nullable()` |
| `sort_order` | `unsignedInteger('sort_order')->default(0)` |
| `is_primary` | `boolean('is_primary')->default(false)` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `index(['product_id', 'sort_order'])`.

<a id="product-variants"></a>
## product_variants

Source: [2026_01_01_000005_create_catalog_tables.php](../database/migrations/2026_01_01_000005_create_catalog_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `product_id` | `foreignId('product_id')->constrained()->cascadeOnDelete()` |
| `name` | `string('name', 80)` |
| `size_ml` | `unsignedSmallInteger('size_ml')->nullable()` |
| `sku` | `string('sku', 64)->unique()` |
| `price` | `decimal('price', 10, 2)->nullable()` |
| `sale_price` | `decimal('sale_price', 10, 2)->nullable()` |
| `stock` | `unsignedInteger('stock')->default(0)` |
| `low_stock_threshold` | `unsignedInteger('low_stock_threshold')->default(5)` |
| `is_active` | `boolean('is_active')->default(true)` |
| `sort_order` | `unsignedInteger('sort_order')->default(0)` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `index(['product_id', 'is_active'])`; `index('stock')`.

<a id="carts"></a>
## carts

Source: [2026_01_01_000006_create_cart_and_wishlist_tables.php](../database/migrations/2026_01_01_000006_create_cart_and_wishlist_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `user_id` | `foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete()` |
| `session_token` | `string('session_token', 64)->nullable()->unique()` |
| `coupon_code` | `string('coupon_code', 50)->nullable()` |
| `created_at, updated_at` | `timestamps()` |

<a id="cart-items"></a>
## cart_items

Source: [2026_01_01_000006_create_cart_and_wishlist_tables.php](../database/migrations/2026_01_01_000006_create_cart_and_wishlist_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `cart_id` | `foreignId('cart_id')->constrained()->cascadeOnDelete()` |
| `product_variant_id` | `foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete()` |
| `quantity` | `unsignedSmallInteger('quantity')->default(1)` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `unique(['cart_id', 'product_variant_id'])`.

<a id="wishlists"></a>
## wishlists

Source: [2026_01_01_000006_create_cart_and_wishlist_tables.php](../database/migrations/2026_01_01_000006_create_cart_and_wishlist_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `user_id` | `foreignId('user_id')->unique()->constrained()->cascadeOnDelete()` |
| `created_at, updated_at` | `timestamps()` |

<a id="wishlist-items"></a>
## wishlist_items

Source: [2026_01_01_000006_create_cart_and_wishlist_tables.php](../database/migrations/2026_01_01_000006_create_cart_and_wishlist_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `wishlist_id` | `foreignId('wishlist_id')->constrained()->cascadeOnDelete()` |
| `product_id` | `foreignId('product_id')->constrained()->cascadeOnDelete()` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `unique(['wishlist_id', 'product_id'])`.

<a id="coupons"></a>
## coupons

Source: [2026_01_01_000007_create_coupons_tables.php](../database/migrations/2026_01_01_000007_create_coupons_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `code` | `string('code', 50)->unique()` |
| `description` | `string('description')->nullable()` |
| `type` | `string('type', 12)` |
| `value` | `decimal('value', 10, 2)` |
| `min_order_amount` | `decimal('min_order_amount', 10, 2)->nullable()` |
| `max_discount` | `decimal('max_discount', 10, 2)->nullable()` |
| `starts_at` | `timestamp('starts_at')->nullable()` |
| `expires_at` | `timestamp('expires_at')->nullable()` |
| `usage_limit` | `unsignedInteger('usage_limit')->nullable()` |
| `per_user_limit` | `unsignedInteger('per_user_limit')->nullable()` |
| `used_count` | `unsignedInteger('used_count')->default(0)` |
| `is_active` | `boolean('is_active')->default(true)` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `index(['is_active', 'expires_at'])`.

<a id="coupon-products"></a>
## coupon_products

Source: [2026_01_01_000007_create_coupons_tables.php](../database/migrations/2026_01_01_000007_create_coupons_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `coupon_id` | `foreignId('coupon_id')->constrained()->cascadeOnDelete()` |
| `product_id` | `foreignId('product_id')->constrained()->cascadeOnDelete()` |

Additional indexes/constraints: `primary(['coupon_id', 'product_id'])`.

<a id="coupon-categories"></a>
## coupon_categories

Source: [2026_01_01_000007_create_coupons_tables.php](../database/migrations/2026_01_01_000007_create_coupons_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `coupon_id` | `foreignId('coupon_id')->constrained()->cascadeOnDelete()` |
| `category_id` | `foreignId('category_id')->constrained()->cascadeOnDelete()` |

Additional indexes/constraints: `primary(['coupon_id', 'category_id'])`.

<a id="orders"></a>
## orders

Source: [2026_01_01_000008_create_orders_tables.php](../database/migrations/2026_01_01_000008_create_orders_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `order_number` | `string('order_number', 32)->unique()` |
| `user_id` | `foreignId('user_id')->nullable()->constrained()->restrictOnDelete()` |
| `idempotency_key` | `string('idempotency_key', 64)->nullable()->unique()` |
| `status` | `string('status', 24)->default('pending')` |
| `payment_method` | `string('payment_method', 24)` |
| `payment_status` | `string('payment_status', 24)->default('pending')` |
| `name` | `string('name')` |
| `email` | `string('email')` |
| `phone` | `string('phone', 20)` |
| `address_line1` | `string('address_line1')` |
| `address_line2` | `string('address_line2')->nullable()` |
| `city` | `string('city', 100)` |
| `state` | `string('state', 100)` |
| `postal_code` | `string('postal_code', 10)` |
| `country` | `string('country', 60)->default('India')` |
| `notes` | `text('notes')->nullable()` |
| `subtotal` | `decimal('subtotal', 12, 2)` |
| `discount_total` | `decimal('discount_total', 12, 2)->default(0)` |
| `shipping_total` | `decimal('shipping_total', 12, 2)->default(0)` |
| `tax_total` | `decimal('tax_total', 12, 2)->default(0)` |
| `grand_total` | `decimal('grand_total', 12, 2)` |
| `refunded_amount` | `decimal('refunded_amount', 12, 2)->default(0)` |
| `coupon_id` | `foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete()` |
| `coupon_code` | `string('coupon_code', 50)->nullable()` |
| `courier_name` | `string('courier_name', 100)->nullable()` |
| `tracking_number` | `string('tracking_number', 100)->nullable()` |
| `tracking_url` | `string('tracking_url')->nullable()` |
| `placed_at` | `timestamp('placed_at')->nullable()` |
| `paid_at` | `timestamp('paid_at')->nullable()` |
| `shipped_at` | `timestamp('shipped_at')->nullable()` |
| `delivered_at` | `timestamp('delivered_at')->nullable()` |
| `cancelled_at` | `timestamp('cancelled_at')->nullable()` |
| `cancel_reason` | `string('cancel_reason')->nullable()` |
| `stock_restored_at` | `timestamp('stock_restored_at')->nullable()` |
| `ip_address` | `string('ip_address', 45)->nullable()` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `index(['user_id', 'status'])`; `index('status')`; `index('payment_status')`; `index('payment_method')`; `index('email')`; `index('created_at')`.

<a id="order-items"></a>
## order_items

Source: [2026_01_01_000008_create_orders_tables.php](../database/migrations/2026_01_01_000008_create_orders_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `order_id` | `foreignId('order_id')->constrained()->cascadeOnDelete()` |
| `product_id` | `foreignId('product_id')->nullable()->constrained('products')->restrictOnDelete()` |
| `product_variant_id` | `foreignId('product_variant_id')->nullable()->constrained('product_variants')->restrictOnDelete()` |
| `product_name` | `string('product_name')` |
| `variant_name` | `string('variant_name', 80)->nullable()` |
| `sku` | `string('sku', 64)` |
| `description_snapshot` | `text('description_snapshot')->nullable()` |
| `image_path` | `string('image_path')->nullable()` |
| `list_price` | `decimal('list_price', 10, 2)` |
| `unit_price` | `decimal('unit_price', 10, 2)` |
| `quantity` | `unsignedSmallInteger('quantity')` |
| `line_total` | `decimal('line_total', 12, 2)` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `index('order_id')`; `index('product_id')`.

<a id="payments"></a>
## payments

Source: [2026_01_01_000008_create_orders_tables.php](../database/migrations/2026_01_01_000008_create_orders_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `order_id` | `foreignId('order_id')->constrained()->restrictOnDelete()` |
| `provider` | `string('provider', 24)` |
| `provider_order_id` | `string('provider_order_id', 80)->nullable()->index()` |
| `provider_payment_id` | `string('provider_payment_id', 80)->nullable()->unique()` |
| `amount` | `decimal('amount', 12, 2)` |
| `currency` | `string('currency', 3)->default('INR')` |
| `status` | `string('status', 24)->default('created')` |
| `method` | `string('method', 40)->nullable()` |
| `error_code` | `string('error_code', 80)->nullable()` |
| `error_description` | `string('error_description')->nullable()` |
| `verified_at` | `timestamp('verified_at')->nullable()` |
| `meta` | `json('meta')->nullable()` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `index(['order_id', 'status'])`.

<a id="payment-webhook-events"></a>
## payment_webhook_events

Source: [2026_01_01_000008_create_orders_tables.php](../database/migrations/2026_01_01_000008_create_orders_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `provider` | `string('provider', 24)` |
| `event_id` | `string('event_id', 100)` |
| `event_type` | `string('event_type', 80)` |
| `payload` | `json('payload')->nullable()` |
| `processed_at` | `timestamp('processed_at')->nullable()` |
| `outcome` | `string('outcome')->nullable()` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `unique(['provider', 'event_id'])`.

<a id="coupon-usages"></a>
## coupon_usages

Source: [2026_01_01_000008_create_orders_tables.php](../database/migrations/2026_01_01_000008_create_orders_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `coupon_id` | `foreignId('coupon_id')->constrained()->restrictOnDelete()` |
| `order_id` | `foreignId('order_id')->constrained()->cascadeOnDelete()` |
| `user_id` | `foreignId('user_id')->nullable()->constrained()->nullOnDelete()` |
| `email` | `string('email')` |
| `discount_amount` | `decimal('discount_amount', 10, 2)` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `unique(['coupon_id', 'order_id'])`; `index(['coupon_id', 'user_id'])`; `index(['coupon_id', 'email'])`.

<a id="order-status-histories"></a>
## order_status_histories

Source: [2026_01_01_000008_create_orders_tables.php](../database/migrations/2026_01_01_000008_create_orders_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `order_id` | `foreignId('order_id')->constrained()->cascadeOnDelete()` |
| `from_status` | `string('from_status', 24)->nullable()` |
| `to_status` | `string('to_status', 24)` |
| `note` | `string('note')->nullable()` |
| `changed_by` | `foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete()` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `index(['order_id', 'created_at'])`.

<a id="inventory-movements"></a>
## inventory_movements

Source: [2026_01_01_000009_create_inventory_movements_table.php](../database/migrations/2026_01_01_000009_create_inventory_movements_table.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `product_variant_id` | `foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete()` |
| `type` | `string('type', 30)` |
| `quantity_change` | `integer('quantity_change')` |
| `stock_after` | `unsignedInteger('stock_after')` |
| `reference_type` | `string('reference_type', 40)->nullable()` |
| `reference_id` | `unsignedBigInteger('reference_id')->nullable()` |
| `user_id` | `foreignId('user_id')->nullable()->constrained()->nullOnDelete()` |
| `note` | `string('note')->nullable()` |
| `created_at` | `timestamp('created_at')->useCurrent()` |

Additional indexes/constraints: `index(['product_variant_id', 'created_at'])`; `index(['reference_type', 'reference_id'])`; `index('type')`.

<a id="reviews"></a>
## reviews

Source: [2026_01_01_000010_create_reviews_table.php](../database/migrations/2026_01_01_000010_create_reviews_table.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `product_id` | `foreignId('product_id')->constrained()->cascadeOnDelete()` |
| `user_id` | `foreignId('user_id')->constrained()->restrictOnDelete()` |
| `order_id` | `foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete()` |
| `rating` | `unsignedTinyInteger('rating')` |
| `title` | `string('title')->nullable()` |
| `body` | `text('body')` |
| `status` | `string('status', 16)->default('pending')` |
| `moderated_by` | `foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete()` |
| `moderated_at` | `timestamp('moderated_at')->nullable()` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `unique(['product_id', 'user_id'])`; `index(['product_id', 'status'])`; `index('status')`.

<a id="contact-enquiries"></a>
## contact_enquiries

Source: [2026_01_01_000011_create_cms_tables.php](../database/migrations/2026_01_01_000011_create_cms_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `name` | `string('name')` |
| `email` | `string('email')` |
| `phone` | `string('phone', 20)->nullable()` |
| `subject` | `string('subject')->nullable()` |
| `message` | `text('message')` |
| `status` | `string('status', 20)->default('new')` |
| `admin_notes` | `text('admin_notes')->nullable()` |
| `ip_address` | `string('ip_address', 45)->nullable()` |
| `handled_by` | `foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete()` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `index('status')`; `index('created_at')`.

<a id="newsletter-subscribers"></a>
## newsletter_subscribers

Source: [2026_01_01_000011_create_cms_tables.php](../database/migrations/2026_01_01_000011_create_cms_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `email` | `string('email')->unique()` |
| `consent_given_at` | `timestamp('consent_given_at')` |
| `consent_ip` | `string('consent_ip', 45)->nullable()` |
| `unsubscribe_token` | `string('unsubscribe_token', 64)->unique()` |
| `unsubscribed_at` | `timestamp('unsubscribed_at')->nullable()` |
| `created_at, updated_at` | `timestamps()` |

<a id="banners"></a>
## banners

Source: [2026_01_01_000011_create_cms_tables.php](../database/migrations/2026_01_01_000011_create_cms_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `title` | `string('title')` |
| `subtitle` | `string('subtitle')->nullable()` |
| `image_path` | `string('image_path')->nullable()` |
| `button_text` | `string('button_text', 60)->nullable()` |
| `link_url` | `string('link_url')->nullable()` |
| `position` | `string('position', 20)->default('promo')` |
| `sort_order` | `unsignedInteger('sort_order')->default(0)` |
| `is_active` | `boolean('is_active')->default(true)` |
| `starts_at` | `timestamp('starts_at')->nullable()` |
| `ends_at` | `timestamp('ends_at')->nullable()` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `index(['position', 'is_active', 'sort_order'])`.

<a id="pages"></a>
## pages

Source: [2026_01_01_000011_create_cms_tables.php](../database/migrations/2026_01_01_000011_create_cms_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `slug` | `string('slug')->unique()` |
| `title` | `string('title')` |
| `content` | `longText('content')->nullable()` |
| `meta_title` | `string('meta_title')->nullable()` |
| `meta_description` | `string('meta_description', 320)->nullable()` |
| `is_published` | `boolean('is_published')->default(false)` |
| `reviewed_at` | `timestamp('reviewed_at')->nullable()` |
| `reviewed_by` | `foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()` |
| `created_at, updated_at` | `timestamps()` |

<a id="faqs"></a>
## faqs

Source: [2026_01_01_000011_create_cms_tables.php](../database/migrations/2026_01_01_000011_create_cms_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `question` | `string('question')` |
| `answer` | `text('answer')` |
| `sort_order` | `unsignedInteger('sort_order')->default(0)` |
| `is_active` | `boolean('is_active')->default(true)` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `index(['is_active', 'sort_order'])`.

<a id="settings"></a>
## settings

Source: [2026_01_01_000011_create_cms_tables.php](../database/migrations/2026_01_01_000011_create_cms_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `key` | `string('key')->unique()` |
| `value` | `text('value')->nullable()` |
| `group` | `string('group', 40)->default('general')` |
| `type` | `string('type', 16)->default('string')` |
| `created_at, updated_at` | `timestamps()` |

Additional indexes/constraints: `index('group')`.

<a id="admin-activity-logs"></a>
## admin_activity_logs

Source: [2026_01_01_000011_create_cms_tables.php](../database/migrations/2026_01_01_000011_create_cms_tables.php).

| Column(s) | Schema declaration |
|---|---|
| `id` | `id()` |
| `user_id` | `foreignId('user_id')->nullable()->constrained()->nullOnDelete()` |
| `action` | `string('action', 60)` |
| `subject_type` | `string('subject_type', 60)->nullable()` |
| `subject_id` | `unsignedBigInteger('subject_id')->nullable()` |
| `description` | `string('description')` |
| `properties` | `json('properties')->nullable()` |
| `ip_address` | `string('ip_address', 45)->nullable()` |
| `user_agent` | `string('user_agent')->nullable()` |
| `created_at` | `timestamp('created_at')->useCurrent()` |

Additional indexes/constraints: `index('action')`; `index(['subject_type', 'subject_id'])`; `index('created_at')`.
