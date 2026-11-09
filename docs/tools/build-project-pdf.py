"""Build the project handbook using local sources and installed Playwright.

Run from the project root with a Python interpreter that has Playwright installed:
    python docs/tools/build-project-pdf.py
No private application records or environment credentials are exported.
"""

from pathlib import Path
import base64
import html
import json
import re
import subprocess

ROOT = Path(__file__).resolve().parents[2]
DOCS = ROOT / "docs"
TITLE = "ARSH PERFUME | Website Documentation"
DATE = "27 October 2026"
PAGES = []


def esc(value):
    return html.escape(str(value))


def p(text):
    return f"<p>{text}</p>"


def h(text):
    return f"<h2>{text}</h2>"


def ul(*items):
    return "<ul>" + "".join(f"<li>{x}</li>" for x in items) + "</ul>"


def steps(*items):
    return "<ol>" + "".join(f"<li>{x}</li>" for x in items) + "</ol>"


def table(headers, rows, cls=""):
    return f'<table class="{cls}"><thead><tr>' + "".join(f"<th>{x}</th>" for x in headers) + "</tr></thead><tbody>" + "".join("<tr>" + "".join(f"<td>{x}</td>" for x in row) + "</tr>" for row in rows) + "</tbody></table>"


def code(text):
    return "<pre>" + esc(text.strip()) + "</pre>"


def note(title, text):
    return f'<aside><strong>{title}</strong><p>{text}</p></aside>'


def source(*paths):
    return '<div class="source">Source: ' + "; ".join(esc(x) for x in paths) + "</div>"


def page(title, body, group="PROJECT HANDBOOK", cls=""):
    PAGES.append({"title": title, "body": body, "group": group, "cls": cls})


def flow(nodes):
    return '<div class="flow">' + '<span class="arrow">↓</span>'.join(f'<div class="flow-node">{x}</div>' for x in nodes) + "</div>"


logo = base64.b64encode((ROOT / "public/images/brand/arsh-logo.jpg").read_bytes()).decode()
page("Website documentation", f'''
<div class="cover-top">PROJECT DOCUMENTATION · VERSION 1.0</div>
<img class="logo" src="data:image/jpeg;base64,{logo}" alt="Arsh Perfume floral emblem">
<div class="eyebrow">FEEL THE BEST QUALITY.</div>
<h1 class="brand">ARSH<br>PERFUME</h1>
<div class="cover-rule"></div>
<div class="cover-subtitle">Website documentation</div>
<p class="cover-description">Customer guide · Administration · Architecture<br>Database · Installation · Operations</p>
<div class="cover-meta">{DATE}<br>Based on the source in this project workspace</div>
''', cls="cover")

# Filled once every section and appendix is known.
page("Contents", "", "READING GUIDE", "contents")

page("Project overview", p("ARSH PERFUME is an India-focused e-commerce website for attars, roll-on perfumes and fragrance oils. It combines a public storefront, customer accounts and an administrator back office in one Laravel application.")
    + h("Purpose and objectives")
    + ul("Help customers discover fragrances, compare available sizes and place orders.", "Keep product prices, stock, discounts and order totals consistent on the server.", "Give the store owner tools to manage products, payments, fulfilment, content and reports.", "Support Cash on Delivery, Razorpay online payments and direct UPI with manual receipt verification.")
    + h("Users and access")
    + table(["User", "Main capabilities"], [("Visitor / guest", "Browse, search, manage a session cart, check out when enabled, track an order and contact the store."), ("Customer", "Guest capabilities plus wishlist, saved addresses, profile, password and own order history."), ("Administrator", "Restricted management screens for catalogue, inventory, orders, customers, content, settings and reports.")])
    + h("Scope of this document")
    + p("This handbook explains the implemented website and how to install, use and maintain it. Appendices contain migration-derived table definitions and the registered application routes. It describes source behavior; it does not certify a production deployment or a real payment-provider integration.")
    + note("Evidence and date", "Reviewed on 27 October 2026. The application route list was generated successfully. An isolated SQLite test run returned 68 passed and 2 failed with 389 assertions. The failures and their practical limits are recorded in the validation section.")
    + source("composer.json", "package.json", "routes/", "app/Services/"))

page("Features and site map", table(["Area", "Implemented features"], [
    ("Discovery", "Home, shop, collections, product detail, search, category/fragrance/price/stock filters and sorting."),
    ("Shopping", "Variants, guest/customer cart, quantity changes, coupons and server-calculated totals."),
    ("Checkout", "Guest or account checkout, Indian shipping address, COD, Razorpay and direct UPI QR."),
    ("Customer account", "Email registration/login, password reset, optional Google sign-in, profile, addresses, own orders and cancellation."),
    ("Engagement", "Wishlist, delivered-purchase reviews, FAQs, contact enquiries and newsletter subscription/unsubscribe."),
    ("Administration", "Dashboard, products/images/sizes, categories, inventory, orders, invoices, customers, coupons and content."),
    ("Operations", "Sales/inventory/customer reports, CSV exports, business settings, activity history, queued notifications and scheduled expiry."),
    ("Public content", "About, contact, policy/help pages, sitemap.xml and robots.txt.")])
    + h("Main navigation paths")
    + flow(["Home → Shop / Collections → Product", "Select size and quantity → Cart → Checkout", "Payment → Order confirmation → Track order", "Sign in → My Account → Orders / Addresses / Wishlist"])
    + note("Availability depends on configuration", "Draft products and unpublished policy pages are intentionally withheld. Guest checkout, COD, UPI and homepage sections depend on the store settings. Google sign-in requires server-side OAuth configuration.")
    + source("routes/web.php", "routes/auth.php", "routes/admin.php"))

page("Customer guide: discovery and cart", h("Browse and find a fragrance")
    + steps("Open <code>/shop</code> to view the catalogue, or <code>/collections</code> to browse categories.", "Search by keyword. Narrow results by category, fragrance family, minimum/maximum price or in-stock availability.", "Sort by newest, price ascending, price descending or popularity.", "Open a product to read the supplied description, fragrance notes, specifications, images and approved reviews.")
    + h("Choose a size and add to cart")
    + p("Prices and stock belong to product variants, such as a bottle size. Choose an active size with a confirmed price and available stock. The cart validates availability; each line allows up to 10 units and remains subject to current stock.")
    + h("Manage the cart")
    + steps("Open <code>/cart</code>. Adjust quantities or remove an item.", "Enter a coupon if you have one. The server checks validity, eligible products, order minimums and usage limits.", "Review the subtotal, discount, shipping, tax information and final amount.", "Continue to checkout. Availability, stock and current prices are checked again when the order is placed.")
    + h("Wishlist and reviews")
    + p("Sign in to save products to the wishlist. Reviews require a delivered purchase of the product and are held for administrator approval. A customer cannot submit multiple reviews of the same product.")
    + note("Cart continuity", "Guests use a browser-session cart. Signed-in users have a persistent cart. The application merges the guest cart during sign-in; the displayed quantities still depend on stock validation.")
    + source("app/Http/Controllers/ShopController.php", "app/Services/CartService.php", "app/Services/CouponService.php", "app/Http/Controllers/ReviewController.php"), "CUSTOMER GUIDE")

page("Customer guide: checkout and tracking", h("Place an order")
    + steps("Review the cart and continue to <code>/checkout</code>. Sign in if guest checkout has been disabled by the store.", "Provide your name, email, phone number and shipping address. The state must match a configured Indian state/territory and the PIN code must have six digits.", "Select an available payment method. Review the final amount and submit once.", "Complete the method-specific payment instructions. Retain the order number shown on confirmation.")
    + table(["Method", "Customer action", "Payment confirmation"], [("Cash on Delivery", "Place the order and pay when collected.", "Administrator collection or delivery updates payment records."), ("Razorpay", "Complete the provider checkout.", "Verified server callback/webhook; a redirect alone is insufficient."), ("Direct UPI QR", "Scan/open the payment link and submit the transaction reference.", "Administrator checks the actual bank receipt before marking paid.")])
    + h("Track and manage orders")
    + p("Use <code>/track-order</code> with the order number and checkout email. Signed-in customers can also open My Account → Orders to view their own order details and history. Customers may cancel their own orders while pending or confirmed.")
    + note("Guest order access", "The browser session that placed a guest order receives access to its protected storefront pages. Keep the order number and email for the separate tracking lookup. A sequential internal database ID is not a customer access credential.")
    + source("app/Http/Requests/CheckoutRequest.php", "app/Http/Controllers/CheckoutController.php", "app/Support/OrderAccess.php", "app/Policies/OrderPolicy.php"), "CUSTOMER GUIDE")

page("Customer accounts and Google sign-in", h("Email accounts")
    + p("Customers register at <code>/register</code>, sign in at <code>/login</code> and sign out using the logout form. Forgotten-password and token-based reset screens are provided. Account screens support profile updates, password changes and saved-address management.")
    + h("Optional Google sign-in")
    + ul("Laravel Socialite handles the Google OAuth redirect and callback.", "Google sign-in remains unavailable until a Client ID and Client Secret are configured.", "A new public Google registration creates a customer account, never an administrator.", "Verified Google email and a stable Google account identifier are required.", "An existing email match does not silently connect accounts. Existing customers sign in locally, then connect Google from My Account after confirming their password.", "Google-only customers can establish email/password sign-in through the account or password-reset flow.")
    + h("Configuration example")
    + code("""GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

php artisan config:clear""")
    + p("Register the exact callback URI with the OAuth provider. Scheme, hostname, port and path must match the address used to open the store. For production, use the actual HTTPS domain. If no redirect override is set, the application derives it from APP_URL.")
    + note("Account access", "Disabled accounts are blocked and signed out on their next web request. Customer order and address policies restrict records to the owning customer.")
    + source("app/Services/GoogleSignInService.php", "routes/auth.php", "docs/google-login-setup.md", "app/Http/Middleware/EnsureUserIsActive.php"), "CUSTOMER GUIDE")

page("Admin guide: catalogue and inventory", h("Access the administration panel")
    + p("Sign in using an active administrator account, then open <code>/admin</code>. There is no public administrator registration. Provision administrators with the dedicated command described in the installation section.")
    + h("Create or update a product")
    + steps("Open Products. Enter the name, SKU, category, description and optional fragrance information.", "Add one or more variants. Each variant has its own SKU, size, price, sale price, stock and low-stock threshold.", "Upload product images and select a primary image. Supported uploads: JPG/JPEG, PNG and WebP, up to 8 files; each at most 3 MB and at least 300 × 300 pixels.", "Keep incomplete products as drafts. Publishing requires at least one active variant with a positive price. A sale price must be below the regular price.", "Use featured/bestseller flags and supplied SEO fields where appropriate. Save and inspect the public product page.")
    + h("Categories and stock")
    + p("Manage category names, slugs, descriptions, images, active status and sort order. A category that still contains products cannot simply be deleted; deactivate it or reassign the products first.")
    + p("Use Inventory to adjust variant stock and record the reason. The inventory ledger records additions, deductions, reservations, cancellations and stocktake differences. Stock is reserved immediately during order placement, so available stock changes before fulfilment.")
    + note("CSV support", "Product CSV export is implemented. A product import route is registered, but its controller method is absent; CSV product import must not be described as a working feature.")
    + source("app/Http/Requests/ProductRequest.php", "app/Http/Controllers/Admin/ProductController.php", "app/Services/InventoryService.php"), "ADMIN GUIDE")

page("Admin guide: orders and payments", h("Process an order")
    + steps("Open Orders and review the customer's address, purchased item snapshots, totals and payment state.", "Confirm payment where required. Unpaid online orders cannot advance through fulfilment.", "Move the order through permitted states: confirm, process, ship, and deliver.", "Add courier/tracking details as they become available. Review the recorded history and invoice.")
    + h("Payment actions")
    + table(["Method", "Administrator responsibility"], [("COD", "Record collected cash when appropriate; delivery automatically marks COD payment as collected."), ("Razorpay", "Use verified gateway records. Investigate failed or mismatched payments before proceeding."), ("UPI", "Inspect the receiving bank/UPI account, compare full amount and reference, confirm receipt, then use Verify UPI payment received.")])
    + h("Cancellation, returns and refunds")
    + p("Cancellation is allowed only by the configured status transition map. Reserved stock is restored once on cancellation. Return restocking is optional. Refunds may be full or partial, up to the remaining refundable amount; a full refund changes the payment status to refunded.")
    + p("Live Razorpay refunds use the gateway integration. For UPI, transfer the refund separately through the bank/UPI app before recording it in the store. A record-only action does not move money to a customer's bank account.")
    + note("Direct UPI review", "A customer-entered reference is a claim awaiting verification. The same verified bank reference cannot settle two orders. The Razorpay unpaid-order timeout excludes direct UPI orders; cancel stale UPI orders manually when appropriate.")
    + source("app/Http/Controllers/Admin/OrderController.php", "app/Services/OrderService.php", "app/Services/UpiPaymentService.php"), "ADMIN GUIDE")

page("Admin guide: content, customers and reports", table(["Module", "Typical tasks"], [("Dashboard", "Review order/revenue metrics, charts and recent operating information."), ("Customers", "Inspect customer details and orders; activate/deactivate accounts."), ("Coupons", "Configure fixed/percentage discounts, validity dates, eligible products/categories, minimum order and usage caps."), ("Banners", "Maintain homepage promotional content, images and ordering."), ("Pages", "Edit, preview, publish or unpublish informational and policy pages."), ("FAQs", "Create and maintain customer help questions and answers."), ("Reviews", "Approve, reject or delete submitted product reviews."), ("Enquiries", "Read contact messages and maintain their handling state."), ("Newsletter", "View subscriptions and export the subscriber list."), ("Reports", "Review sales, inventory and customer reports; use supported exports."), ("Activity log", "Inspect administrator actions and recorded changes.")])
    + h("Revenue definition")
    + p("The shared revenue scope includes paid or refunded orders and excludes cancelled/returned orders. Net revenue per included order is grand total minus refunded amount. A pending COD order does not count as paid revenue until collection is recorded.")
    + h("Content publication")
    + p("Seeded policy pages contain owner-confirmation placeholders and start unpublished. Replace and review those texts before publishing. Product claims, fragrance notes and legal/business details must come from the owner.")
    + source("routes/admin.php", "app/Models/Order.php", "app/Services/ReportService.php", "database/seeders/PageSeeder.php"), "ADMIN GUIDE")

page("Admin guide: store settings", p("Settings are stored in the database and exposed through Admin → Settings. They override defaults from <code>config/arsh.php</code>. Environment secrets remain in server configuration.")
    + table(["Group", "Examples and effect"], [("Business", "Store name, tagline, contact details, address, business hours, GSTIN, logo/favicon, about text, footer text and links."), ("Social", "Instagram, Facebook, YouTube and WhatsApp links."), ("Commerce", "Tax enabled/rate, inclusive pricing, flat shipping, free-shipping threshold, COD, guest checkout and low-stock settings."), ("UPI payments", "Enable direct UPI and provide the payee name and UPI ID. All required receiving details must be valid."), ("Invoice", "Invoice prefix and footer text."), ("Notifications", "Administrative order-notification destination and notification settings."), ("Homepage", "Hero text and visibility of catalogue, reviews, newsletter and social sections.")])
    + h("Setup sequence for the owner")
    + steps("Complete the business identity, contact details, branding and invoice text.", "Confirm tax and shipping rules and decide whether to allow guest checkout and COD.", "Configure the UPI receiving account only if direct UPI will be accepted.", "Prepare products, variants, actual stock and product photography.", "Review and publish content pages, then perform a controlled checkout rehearsal.")
    + note("Receiving-account snapshots", "Changing the UPI ID affects new checkouts. Existing UPI orders retain the receiving details captured when they were placed; verify against that order's recorded destination.")
    + source("config/arsh.php", "app/Models/Setting.php", "app/Http/Controllers/Admin/SettingController.php"), "ADMIN GUIDE")

page("Technology stack and structure", table(["Layer", "Project technology"], [("Backend", "PHP ^8.2; Laravel ^12.0; Eloquent ORM; custom authentication controllers."), ("Identity", "Laravel Socialite ^5.31 for Google account sign-in."), ("Database", "MySQL-oriented migrations; in-memory SQLite for the automated suite."), ("Rendering", "Server-rendered Blade views with Tailwind CSS 4 and Alpine.js 3."), ("Assets", "Vite 7 and laravel-vite-plugin 2; Node.js for building frontend assets."), ("Browser features", "Chart.js 4 for admin charts, Framer Motion DOM APIs for motion, qrcode for local UPI QR generation, Axios."), ("Testing", "PHPUnit ^11.5 with Laravel test helpers, factories and Mockery.")])
    + h("Directory guide")
    + code("""app/Http/Controllers/    Public, Auth and Admin request handlers
app/Http/Requests/       Checkout and product validation
app/Models/             Eloquent records and relationships
app/Services/           Business rules and operations
app/Policies/           Order and address authorization
app/Support/            Money, order access and helpers
bootstrap/app.php       Boot, routes and middleware
config/                 Laravel and store configuration
database/               Migrations, seeders and factories
resources/views/        Blade screens and components
resources/css|js/       Styles and browser behavior
routes/                 Web, auth, admin and scheduling
tests/                  Unit and feature tests
public/                 Web document root and built assets
storage/                Runtime data, logs and uploads""")
    + p("The store is a single Laravel application with progressively enhanced server-rendered pages. Composer and npm lock files are present; keep them when installing reproducible dependencies.")
    + source("composer.json", "package.json", "vite.config.js"), "TECHNICAL GUIDE")

page("Architecture and request flow", flow(["Browser: Blade HTML + CSS + Alpine / browser modules", "HTTP routes: web.php · auth.php · admin.php", "Middleware: session · CSRF · authentication · active user · admin", "Controllers and Form Requests: validate and authorize", "Services: pricing · carts · orders · inventory · payments", "Eloquent models → MySQL", "Response / views + events → queued notifications"])
    + h("Responsibilities")
    + table(["Component", "Role"], [("Controllers", "Coordinate the request and response; delegate business operations."), ("Services", "Own reusable transaction, pricing, stock and payment rules."), ("Models", "Represent tables, relationships, query scopes and record helpers."), ("Policies / middleware", "Protect account ownership and administrative access."), ("Blade / JavaScript", "Render content and add interactions, charts, QR generation and motion.")])
    + h("Background work")
    + p("Order placement emits an event whose listener sends customer and administrative notifications. Queue workers deliver queued work. The scheduler expires unpaid Razorpay orders and prunes failed jobs. Webhooks enter through a signed provider endpoint rather than a customer session.")
    + source("bootstrap/app.php", "app/Events/OrderPlaced.php", "app/Listeners/SendOrderPlacedNotifications.php", "routes/console.php"), "TECHNICAL GUIDE")

page("Service responsibilities", table(["Service", "Responsibility"], [("CartService", "Guest/customer carts, cart merging, item availability, quantities and summaries."), ("PricingService", "Shared subtotal, discount, shipping and tax calculations in integer paise."), ("CouponService", "Eligibility, validity periods, order minimums, usage limits and discount caps."), ("OrderService", "Transactional placement, stock reservation, snapshots, state transitions, cancellation and refunds."), ("InventoryService", "Controlled stock changes and inventory movement records."), ("RazorpayService", "Provider API interactions, signed verification, simulated local gateway and refunds."), ("PaymentService", "Verified payment handling, capture/failure records and payment state changes."), ("UpiPaymentService", "Availability, account snapshots, payment URI, submitted references and admin receipt verification."), ("GoogleSignInService", "Google identity validation, safe registration and explicit linking."), ("AdminProvisioner", "Controlled creation/update of administrator accounts."), ("ReportService", "Sales, inventory and customer report queries/aggregations."), ("ActivityLogger", "Administrative audit entries.")])
    + h("Supporting helpers")
    + ul("<code>Money</code>: converts decimal strings to/from integer paise and formats monetary amounts.", "<code>setting()</code>: reads a runtime setting with a fallback.", "<code>money()</code>: formats a money value for display.", "<code>media_url()</code>: builds a media URL or fallback.", "<code>OrderAccess</code>: centralizes storefront order access for owners or permitted browser sessions.")
    + source("app/Services/", "app/Support/Money.php", "app/Support/helpers.php", "app/Support/OrderAccess.php"), "TECHNICAL GUIDE")

page("Checkout integrity, pricing and inventory", h("Transactional placement")
    + steps("Validate checkout fields and the selected method; obtain the cart and optional idempotency key.", "Begin a database transaction and lock variants in stable ID order.", "Recheck active product/variant status, confirmed prices and available stock.", "Lock and revalidate a coupon where supplied. Calculate authoritative totals.", "Create the order, item/address snapshots, payment record and status history.", "Reserve stock immediately and write inventory movements; clear the cart.", "Commit and emit the placed-order event. A repeated idempotency key returns the original order.")
    + h("Totals")
    + p("Subtotal is the sum of current unit price × quantity. Discounts are bounded by eligible amounts. Shipping is evaluated against the discounted subtotal and the configured free-shipping threshold. With inclusive tax, the tax portion is informational; with exclusive tax, tax is added to the discounted subtotal. Shipping is then included in the final amount.")
    + code("""Discounted subtotal = subtotal - validated discount
Inclusive-tax total = discounted subtotal + shipping
Exclusive-tax total = discounted subtotal + tax + shipping
Net included revenue = grand total - refunded amount""")
    + h("Preserved purchase history")
    + p("Order items snapshot product/variant information, quantities and prices. The delivery address is also copied to the order. Later edits to the catalogue or saved address do not rewrite an existing purchase.")
    + note("Concurrency and stock", "The server does not trust a browser-supplied total. Row locks and transactional stock operations protect checkout races. Cancellation uses stock_restored_at to avoid restoring the same reservation twice.")
    + source("app/Services/OrderService.php", "app/Services/PricingService.php", "app/Services/InventoryService.php"), "TECHNICAL GUIDE")

page("Order lifecycle", p("Fulfilment status and payment status are separate fields. A shipment status change does not substitute for verified payment.")
    + flow(["Pending → Confirmed → Processing", "Shipped → Out for delivery → Delivered", "Cancellation / return only through the permitted transitions below"])
    + table(["Current status", "Allowed next statuses"], [("pending", "confirmed, cancelled"), ("confirmed", "processing, cancelled"), ("processing", "shipped, cancelled"), ("shipped", "out_for_delivery, delivered, returned"), ("out_for_delivery", "delivered, returned"), ("delivered", "returned"), ("cancelled", "None (terminal)"), ("returned", "None (terminal)")])
    + h("Payment states and operating rules")
    + ul("Order payment states: pending, paid, failed and refunded. Individual gateway payment records can use provider/attempt states such as created.", "Customers can cancel only pending or confirmed orders that they own.", "Unpaid online orders cannot advance through fulfilment.", "COD is marked paid on delivery or explicit cash collection.", "Unpaid Razorpay reservations expire after 30 minutes and are collected by the task running every ten minutes. Actual release occurs on the next successful scheduled run.", "Direct UPI awaiting review is excluded from that expiry task.")
    + source("config/arsh.php", "app/Services/OrderService.php", "app/Console/Commands/ExpireUnpaidOrders.php", "routes/console.php"), "TECHNICAL GUIDE")

page("Payment integrations", h("Razorpay online payments")
    + p("Configure RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET and RAZORPAY_WEBHOOK_SECRET on the server. Register <code>POST /webhooks/razorpay</code> as the provider webhook endpoint for capture and failure notifications. Use test credentials before configuring live credentials.")
    + ul("The amount comes from the saved order total and is checked against provider values.", "Checkout callbacks use HMAC-SHA256 over order/payment identifiers; webhooks verify the raw request body.", "Recorded provider event identifiers make duplicate/retried webhooks idempotent.", "A successful browser redirect alone never marks an order paid.", "A simulated gateway exists for local non-production use. Production requires real provider configuration.")
    + h("Direct UPI QR")
    + flow(["Checkout snapshots payee name + UPI ID + order amount", "Browser generates a local QR / upi:// payment URI", "Customer transfers funds and submits an unverified reference", "Administrator verifies actual receipt → payment marked paid"])
    + p("QR generation uses the bundled qrcode library. The code does not call an external QR-image service. References are normalized and must contain 6–64 letters or digits. A verified reference is unique across settled orders.")
    + note("Financial verification boundary", "This documentation run performed no real bank transfer, real Razorpay charge/refund or live Google consent round trip. Automated tests use controlled or simulated integration paths.")
    + source("app/Services/RazorpayService.php", "app/Services/PaymentService.php", "app/Services/UpiPaymentService.php", "resources/js/"), "TECHNICAL GUIDE")

page("Database model and relationships", p("The migration source declares <strong>36 tables: 29 application tables and 7 framework support tables</strong>. Laravel also creates the migrations bookkeeping table. The appendix records column definitions from migration source rather than private database contents.")
    + table(["Domain", "Tables"], [("Accounts", "roles, users, user_addresses"), ("Catalogue", "categories, products, product_images, product_variants, reviews"), ("Shopping", "carts, cart_items, wishlists, wishlist_items"), ("Discounts", "coupons, coupon_products, coupon_categories, coupon_usages"), ("Orders", "orders, order_items, order_status_histories"), ("Payments", "payments, payment_webhook_events"), ("Inventory", "inventory_movements"), ("Content / operations", "contact_enquiries, newsletter_subscribers, banners, pages, faqs, settings, admin_activity_logs"), ("Framework", "password_reset_tokens, sessions, cache, cache_locks, jobs, job_batches, failed_jobs")])
    + h("Core relationship map")
    + code("""Role 1 ── * User 1 ── * UserAddress
Category 1 ── * Product 1 ── * ProductVariant
                       ├── * ProductImage
                       └── * Review
Cart 1 ── * CartItem * ── 1 ProductVariant
User 1 ── * Order 1 ── * OrderItem
                 ├── * Payment
                 └── * OrderStatusHistory
ProductVariant 1 ── * InventoryMovement
Coupon ── products / categories / recorded usages""")
    + p("Money uses decimal persistence and integer-paise checkout arithmetic. Products are soft deleted. User Google identity/password-login fields were added in the October 2026 migration.")
    + source("database/migrations/", "app/Models/", "docs/DATABASE_REFERENCE.md"), "TECHNICAL GUIDE")

page("Security and access controls", table(["Control", "Implemented behavior"], [("Authentication", "Session-based login, hashed passwords, password reset, session refresh and optional Google OAuth."), ("Role protection", "Admin routes require web, auth and admin middleware. Public forms do not assign administrator privileges."), ("Inactive accounts", "Web middleware signs out disabled users and blocks continued access."), ("Record ownership", "Policies and OrderAccess protect customer orders, addresses and permitted guest sessions."), ("CSRF", "Web forms use CSRF protection. The Razorpay webhook exception relies on its HMAC signature."), ("Input checks", "Form Requests and controller validation constrain checkout, products, images and other inputs."), ("Payment checks", "Server totals, signed verification, amount checks, event deduplication and manual bank-receipt verification for UPI."), ("Upload handling", "Raster image type/size/dimension checks and randomized storage names."), ("Auditability", "Order histories, inventory movement records and administrator activity logs.")])
    + h("Configured rate limits")
    + p("Per-minute limits: login 5; OAuth 10; registration 5; password reset 5; checkout 10; coupon attempts 15; tracking 10; contact 5; newsletter 5; reviews 5. Keys vary by endpoint and use IP, customer identity or email plus IP.")
    + h("Configuration and publication")
    + p("Keep credentials in the server environment. Publish only the public document root, disable production debug output and use HTTPS. Customer data, passwords, real keys and private database records are deliberately excluded from this handbook.")
    + source("bootstrap/app.php", "app/Providers/AppServiceProvider.php", "app/Policies/", "app/Http/Middleware/", "app/Http/Requests/"), "TECHNICAL GUIDE")

page("Installation: local development", h("Prerequisites")
    + p("PHP 8.2+ with the required Laravel/database extensions, Composer 2, Node.js compatible with the installed Vite version, npm and MySQL 8. Vite 7 requires Node 20.19+ or 22.12+ according to its installed package metadata. Enable pdo_mysql, mbstring, openssl, tokenizer, XML/DOM, ctype, fileinfo, curl and GD for the documented functionality; SQLite extensions are needed for the test suite.")
    + h("Fresh installation in PowerShell")
    + code("""Set-Location C:\\xampp\\htdocs\\arsh-perfume
composer install
# Copy only when .env does not already exist:
Copy-Item .env.example .env
php artisan key:generate
# Create a database, then configure DB_* in .env.
php artisan migrate --seed
npm ci
npm run build
php artisan storage:link
php artisan arsh:create-admin owner@example.com --name="Store Owner"
php artisan serve""")
    + p("The administrator command prompts for a password. Set APP_URL to the address you actually use; the default development address is <code>http://localhost:8000</code>. For day-to-day asset development, run <code>npm run dev</code> in a separate terminal.")
    + h("XAMPP configuration")
    + p("Point the Apache virtual host at <code>C:/xampp/htdocs/arsh-perfume/public</code>, enable mod_rewrite and AllowOverride All, and start MySQL. The PHP CLI and Apache PHP configurations may differ. Ensure the web user can write storage and bootstrap/cache. Windows storage-link creation may require Developer Mode or an appropriately privileged terminal.")
    + note("Fresh setup only", "Do not replace an existing .env, regenerate a deployed APP_KEY, reseed owner data or rebuild an existing database just to read this documentation. The handwritten SQL schema predates the Google migration; migrations are authoritative.")
    + source(".env.example", "composer.json", "node_modules/vite/package.json", "database/seeders/"), "SETUP AND OPERATIONS")

page("Environment and runtime configuration", table(["Variables", "Purpose"], [("APP_NAME / APP_ENV / APP_DEBUG / APP_URL", "Application identity, environment, diagnostics and canonical base URL."), ("APP_KEY", "Application encryption key. Create once on a fresh setup and retain securely."), ("DB_CONNECTION / DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD", "Database driver and connection. Use a dedicated production database account."), ("SESSION_DRIVER / SESSION_SECURE_COOKIE", "Session storage and HTTPS-only cookie behavior."), ("CACHE_STORE / QUEUE_CONNECTION", "Cache and background-job drivers."), ("MAIL_*", "Mailer, SMTP endpoint, credentials and sender identity. The log driver does not deliver email."), ("MEDIA_DISK / FILESYSTEM_DISK / AWS_*", "Upload/storage configuration. Cloud disks require compatible configured adapters."), ("RAZORPAY_*", "Gateway API ID/secret and webhook verification secret."), ("GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET / GOOGLE_REDIRECT_URI", "OAuth credentials and optional exact callback override."), ("ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD", "One-time seeder/provisioning inputs. Remove after initial use."), ("BCRYPT_ROUNDS", "Password-hash cost.")])
    + h("Local database example — placeholders only")
    + code("""APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arsh_perfume
DB_USERNAME=arsh_app
DB_PASSWORD=replace-with-your-local-password""")
    + p("After changing environment configuration, clear cached values with <code>php artisan config:clear</code>. Rebuild production configuration caches during deployment. Change owner-facing business options through Admin → Settings.")
    + source(".env.example", "config/", "docs/CONFIG_REFERENCE.md"), "SETUP AND OPERATIONS", "configuration")

page("Deployment, queues and maintenance", h("Production release sequence")
    + steps("Prepare PHP, MySQL, HTTPS and a web-server document root pointing to public/. Keep environment secrets outside version control.", "Set APP_ENV=production, APP_DEBUG=false, the HTTPS APP_URL and SESSION_SECURE_COOKIE=true.", "Install locked PHP dependencies and build frontend assets. Apply migrations using the normal release procedure and an available backup.", "Verify the storage link and writable runtime directories. Cache configuration/routes/views/events.", "Run the scheduler every minute and maintain a supervised queue worker. Restart workers after a release.", "Verify owner content, shipping/tax settings, notifications and payment methods with controlled test orders.")
    + code("""composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart""")
    + h("Background processes")
    + code("""# Keep the worker running under a service manager:
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
# Invoke once per minute using cron / Windows Task Scheduler:
php artisan schedule:run""")
    + p("The schedule expires unpaid Razorpay reservations every ten minutes and prunes failed jobs older than 168 hours daily. The built-in health route is <code>/up</code>. Back up the database, uploaded media and deployment configuration; keep protected copies and rehearse restoration. Review application logs and failed jobs regularly.")
    + source("routes/console.php", "bootstrap/app.php", "config/queue.php", "config/logging.php"), "SETUP AND OPERATIONS")

page("Validation and current limitations", h("Verification performed for this document")
    + table(["Check", "Observed result on 27 October 2026"], [("Registered routes", "php artisan route:list --json completed; 140 registered routes, including framework/support routes."), ("Automated suite", "php artisan test --compact using an explicit testing environment and in-memory SQLite: 68 passed, 2 failed; 389 assertions."), ("Source inspection", "Routes, services, middleware, validators, migrations, seeders, manifests and project references reviewed."), ("Live integration", "No real Razorpay charge/refund, bank payment, Google consent or production deployment verified in this documentation task.")])
    + h("Two existing test failures")
    + ul("<code>AdminProductTest::valid image is stored with random name</code>: CLI PHP lacks the GD extension, so the test cannot generate its fixture image.", "<code>PaymentWebhookTest::capture with wrong amount is recorded but does not pay the order</code>: the assertion expects payment-attempt status pending, while the actual record remains created. The order is still pending; the disagreement is about the attempt state.")
    + h("Known source gaps")
    + ul("CSV product import has a registered route but no controller import method.", "Category ordering uses numeric sort values; drag-and-drop ordering is not implemented.", "The opening README status and some historical notes predate the installed dependencies and later tests.", "Product seeds are incomplete drafts; policy seeds are unpublished owner-confirmation placeholders.", "Automatic SMS/WhatsApp notifications, multi-currency checkout, multilingual content and a dedicated external search engine are not implemented.")
    + note("Interpreting test coverage", "A partly passing suite is useful evidence, not a claim that all features are ready for production. Resolve the two failures and exercise real configured integrations before treating the installation as fully validated.")
    + source("tests/", "phpunit.xml", "routes/admin.php", "app/Http/Controllers/Admin/ProductController.php"), "SETUP AND OPERATIONS")

page("Troubleshooting and handover", table(["Symptom", "First checks"], [("Blank/error page", "Inspect storage/logs/laravel.log, runtime directory permissions, PHP extensions and environment configuration."), ("Unstyled pages / missing assets", "Run npm ci and npm run build; verify public/build/manifest.json and stale Vite hot-file state."), ("Database connection error", "Check MySQL availability and DB_* values, then clear configuration cache. Do not recreate existing tables as a first step."), ("Images missing", "Verify the configured media disk, uploaded file and public storage link."), ("Google button disabled", "Set server OAuth credentials, confirm exact callback URI and clear cached configuration."), ("Email not delivered", "Check mail driver/settings, worker health and failed jobs. The log driver only writes a log."), ("Online order remains unpaid", "Inspect verified provider records, webhook URL/signature/amount and delivery logs; for UPI, verify actual receipt manually."), ("Stock remains reserved", "Check the scheduler and expiry task for unpaid Razorpay orders; review stale UPI orders manually."), ("Administrator access denied", "Confirm an active admin account created through arsh:create-admin and check session state."), ("Image test fails", "Enable GD in the PHP CLI configuration used to run the test command.")])
    + h("Owner handover checklist")
    + ul("Administrator access and recovery are established.", "Products, sizes, prices, stock, images and claims are confirmed.", "Business contact details, shipping/tax rules and content pages are reviewed.", "Payment integrations and receipt/refund procedures are rehearsed.", "Mail, queue, scheduler, monitoring and restore procedures are assigned.")
    + h("Source references")
    + p("Start with README.md, docs/ARCHITECTURE.md, docs/DEVELOPER_GUIDE.md, docs/CONFIG_REFERENCE.md and docs/google-login-setup.md. Prefer current code and migrations where historical notes disagree. The PDF's appendices are a source snapshot, not a database dump.")
    + source("docs/", "README.md", "app/", "database/migrations/"), "SETUP AND OPERATIONS", "compact")

# Append the full migration-derived dictionary already maintained in this repo.
# These definitions preserve precision, indexes, foreign keys and defaults.
reference = (DOCS / "DATABASE_REFERENCE.md").read_text(encoding="utf-8")
definitions = []
for block in re.split(r"(?m)^## ", reference)[1:]:
    name, _, body = block.partition("\n")
    if name.strip() == "Tables":
        continue
    rows = []
    for line in body.splitlines():
        if line.startswith("| ") and "---" not in line and not line.startswith("| Column"):
            cells = [x.strip() for x in line.strip().strip("|").split("|")]
            if len(cells) == 2:
                rows.append(tuple(esc(x).replace("`", "") for x in cells))
    # The source dictionary has declarations in fenced PHP blocks rather than
    # conventional column tables. Keep each declaration in the exported table.
    if not rows:
        fenced = re.findall(r"```(?:php)?\n(.*?)```", body, re.S)
        for fragment in fenced:
            for line in fragment.splitlines():
                line = line.strip()
                if line:
                    rows.append((esc(line),))
    for constraints in re.findall(r"(?m)^Additional indexes/constraints: (.+)$", body):
        rows.append(("Indexes / constraints", esc(constraints.replace("`", ""))))
    if rows:
        definitions.append((name.strip(), rows))

# Render a bounded number of declaration lines per page, carrying table titles
# across page breaks. This keeps every migration detail readable.
dictionary_units = []
for name, rows in definitions:
    for offset in range(0, len(rows), 24):
        part = rows[offset:offset + 24]
        label = name + (" (continued)" if offset else "")
        header = ["Column / declaration", "Definition"] if len(part[0]) == 2 else ["Migration declaration"]
        cost = sum(max(1, (len(" ".join(re.sub('<[^>]+>', '', x) for x in row)) + 94) // 95) for row in part) + 5
        dictionary_units.append((label, table(header, part, "schema-table"), cost))
pending, budget = [], 0
dictionary_index = 1
for name, markup, cost in dictionary_units:
    if pending and budget + cost > 36:
        page(f"Database dictionary · {dictionary_index}", "".join(pending), "APPENDIX A · MIGRATION SOURCE", "dictionary")
        dictionary_index += 1
        pending, budget = [], 0
    pending.append(h(name) + markup)
    budget += cost
if pending:
    page(f"Database dictionary · {dictionary_index}", "".join(pending), "APPENDIX A · MIGRATION SOURCE", "dictionary")

# Always regenerate routes from the application rather than copying old counts.
route_result = subprocess.run(["php", "artisan", "route:list", "--json"], cwd=ROOT, capture_output=True, text=True, encoding="utf-8", check=True)
routes = json.loads(route_result.stdout)
for start in range(0, len(routes), 18):
    rows = []
    for route in routes[start:start + 18]:
        rows.append((esc(route["method"].replace("|HEAD", "")), '<code>/' + esc(route["uri"].lstrip("/")) + '</code><br><span class="route-name">' + esc(route.get("name") or "(unnamed)") + '</span>', esc(", ".join(route.get("middleware", []))) or "—"))
    intro = p("GET routes also accept HEAD. Middleware names are shown as registered; admin routes use the web/auth/admin stack. These are browser/session endpoints, not a separate public JSON API. The product-import route is incomplete as noted earlier.") if start == 0 else ""
    page(f"Route reference · {start // 18 + 1}", intro + table(["Method", "Path / route name", "Middleware"], rows, "routes-table"), "APPENDIX B · REGISTERED ROUTES", "routes")

page("Glossary and document maintenance", table(["Term", "Meaning in this project"], [("Variant", "A separately priced and stocked size/option of a product."), ("Paise", "Integer monetary unit used for checkout arithmetic; 100 paise = INR 1."), ("Snapshot", "Purchase-time copy of item, address or UPI receiving details."), ("Reservation", "Stock deduction when an order is placed, before shipment."), ("Idempotency", "Repeated submissions/events produce one business effect."), ("Webhook", "Signed server-to-server payment notification."), ("UTR / reference", "Customer-submitted bank transaction reference; requires receipt verification."), ("Migration", "Versioned table/schema change; authoritative database structure."), ("Seeder", "Code creating initial roles, draft catalogue and content placeholders."), ("Queue worker", "Background process delivering queued work such as notifications."), ("Scheduler", "Periodic command runner used for expiry and maintenance."), ("CMS", "Administration tools for content, banners, FAQs and published pages.")])
    + h("Regenerate this handbook")
    + code("python docs/tools/build-project-pdf.py")
    + p("The generator uses Python with Playwright and an installed Chromium browser. It reads migration reference text and runs the application's route-list command. It does not export environment secrets or query private store records. Narrative content and the dated test result are maintained in the generator; update them after implementation changes or a new verification run.")
    + p("Outputs: <code>docs/ARSH_PERFUME_DOCUMENTATION.html</code> and <code>docs/ARSH_PERFUME_DOCUMENTATION.pdf</code>. The HTML embeds its brand image and styles for convenient editing and printing. PDF navigation includes linked contents and document outlines.")
    + source("docs/tools/build-project-pdf.py", "docs/DATABASE_REFERENCE.md", "routes/"), "DOCUMENT CONTROL")

contents = []
for index, item in enumerate(PAGES):
    if index < 2:
        continue
    if item["group"].startswith("APPENDIX"):
        if index and PAGES[index - 1]["group"] == item["group"]:
            continue
        label = "Database dictionary (all 36 tables)" if "APPENDIX A" in item["group"] else f"Registered route reference ({len(routes)} routes)"
    else:
        label = item["title"]
    contents.append(f'<a href="#page-{index + 1}"><span>{esc(label)}</span><b>{index + 1:02}</b></a>')
PAGES[1]["body"] = p("A practical guide for the store owner, administrators and developers. Read the customer/admin sections for daily use, the technical sections for implementation, and the appendices for exact schema and route details.") + '<nav class="toc">' + "".join(contents) + '</nav>'

CSS = """
@page { size: A4; margin: 16mm; }
* { box-sizing: border-box; }
html { background: #e9e6df; }
body { margin: 0; color: #202a2a; font: 10pt/1.45 'Segoe UI', Arial, sans-serif; }
.sheet { position: relative; width: 178mm; height: 264mm; margin: 12mm auto; padding: 0; background: #fff; break-after: page; }
.sheet:last-child { break-after: auto; }
.page-head { height: 13mm; border-bottom: 1px solid #d6d3c9; display: flex; justify-content: space-between; align-items: flex-start; font-size: 7.5pt; letter-spacing: 1px; color: #6f716a; padding-top: 1mm; }
.page-head b { color: #795e31; }
.body { padding-top: 7mm; padding-bottom: 12mm; }
.body > h1 { margin: 0 0 5mm; font: 24pt/1.15 Georgia, serif; color: #173b36; }
h2 { margin: 5mm 0 2.5mm; color: #795e31; font-size: 12pt; font-weight: 650; }
p { margin: 0 0 3.4mm; }
ul,ol { padding-left: 5.5mm; margin: 0 0 4mm; }
li { margin: 0 0 2mm; padding-left: 1mm; }
strong { font-weight: 650; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 2mm 0 3.5mm; font-size: 8.8pt; line-height: 1.37; }
th { background: #173b36; color: #fff; font-weight: 600; text-align: left; padding: 2.3mm; }
td { vertical-align: top; padding: 2mm 2.5mm; border-bottom: 1px solid #dce2df; overflow-wrap: anywhere; }
tr:nth-child(even) td { background: #f3f5f2; }
th:first-child,td:first-child { width: 31%; }
pre { white-space: pre-wrap; overflow-wrap: anywhere; background: #f0f3ef; border-left: 3px solid #b69962; padding: 3.8mm; margin: 2.5mm 0 4mm; font: 8.5pt/1.5 Consolas,'Courier New',monospace; }
code { font: .88em Consolas,'Courier New',monospace; overflow-wrap: anywhere; }
aside { padding: 3.5mm 4mm; background: #f8f3e8; border-left: 3px solid #b69962; margin: 4mm 0; font-size: 9.2pt; }
aside p { margin: 1mm 0 0; }
.source { margin-top: 3mm; color: #68736b; font: 7.3pt/1.5 Consolas,monospace; overflow-wrap: anywhere; }
.page-foot { position: absolute; bottom: 0; width: 100%; height: 8mm; border-top: 1px solid #d6d3c9; display: flex; align-items: flex-end; justify-content: space-between; color: #767970; font-size: 7.2pt; }
.page-foot b { color: #173b36; font-size: 9pt; }
.flow { display: flex; flex-direction: column; align-items: center; margin: 4mm 0; }
.flow-node { width: 92%; padding: 2mm 4mm; border: 1px solid #cad8d1; border-radius: 2mm; background: #f2f6f2; text-align: center; font-weight: 600; font-size: 9.3pt; }
.arrow { color: #a18a58; font-size: 14pt; line-height: 1.2; }
.cover { color: #f3ead8; background: #102923; padding: 14mm 13mm; }
.cover .page-head,.cover .page-foot { display: none; }
.cover .body { padding: 0; }
.cover-top { color: #cfb885; font-size: 8pt; letter-spacing: 1.9px; }
.logo { width: 42mm; height: 42mm; object-fit: cover; display: block; margin: 16mm 0 11mm; border-radius: 50%; }
.eyebrow { color: #cfb885; letter-spacing: 2.5px; font-size: 8.3pt; }
.cover h1.brand { color: #fff8e9; font: 49pt/1.04 Georgia,serif; margin: 6mm 0 8mm; letter-spacing: 1px; }
.cover-rule { height: 1px; background: #b99d62; width: 30mm; margin: 8mm 0; }
.cover-subtitle { font: 24pt Georgia,serif; margin: 0 0 5mm; }
.cover-description { color: #d5dfd3; font-size: 11pt; line-height: 1.6; }
.cover-meta { color: #b4c3b7; position: absolute; bottom: 15mm; font-size: 8.5pt; line-height: 1.8; }
.contents .body > h1 { margin-bottom: 4mm; }
.contents p { font-size: 9.4pt; }
.toc { margin-top: 5mm; }
.toc a { display: flex; justify-content: space-between; gap: 4mm; border-bottom: 1px dotted #d0d7d0; color: #243e34; text-decoration: none; padding: 1.45mm 0; font-size: 9.1pt; line-height: 1.25; }
.toc b { color: #977744; font-weight: 600; }
.dictionary .body > h1,.routes .body > h1 { font-size: 22pt; margin-bottom: 4mm; }
.dictionary h2 { margin: 3mm 0 1.5mm; font: 10.5pt Consolas,monospace; color: #173b36; }
.schema-table { font: 7.4pt/1.35 Consolas,monospace; margin-bottom: 3mm; }
.schema-table td,.schema-table th { padding: 1.4mm 2mm; }
.schema-table th:first-child,.schema-table td:first-child { width: auto; }
.schema-table:has(th:nth-child(2)) th:first-child,.schema-table:has(th:nth-child(2)) td:first-child { width: 28%; }
.routes p { font-size: 9pt; }
.routes-table { font-size: 8pt; line-height: 1.25; }
.routes-table td,.routes-table th { padding: 1.5mm 2mm; }
.routes-table th:first-child,.routes-table td:first-child { width: 13%; }
.routes-table th:nth-child(2) { width: 64%; }
.route-name { color: #6c7c70; font: 7pt Consolas,monospace; }
.configuration table { font-size: 8.3pt; }
.configuration td,.configuration th { padding: 1.5mm 2mm; }
.configuration pre { font-size: 8pt; line-height: 1.45; padding: 3mm; }
.compact table { font-size: 8.5pt; }
.compact td { padding: 1.8mm 2.5mm; }
@media print { html { background: white; } .sheet { margin: 0; } * { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
"""

sections = []
for index, item in enumerate(PAGES, 1):
    title = "" if item["cls"] == "cover" else f'<h1>{esc(item["title"])}</h1>'
    sections.append(f'''<section id="page-{index}" class="sheet {item['cls']}">
    <header class="page-head"><b>ARSH PERFUME</b><span>{esc(item['group'])}</span></header>
    <main class="body">{title}{item['body']}</main>
    <footer class="page-foot"><span>Website documentation · {DATE}</span><b>{index:02} / {len(PAGES):02}</b></footer>
    </section>''')
document = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' + TITLE + '</title><style>' + CSS + '</style></head><body>' + "".join(sections) + '</body></html>'
html_path = DOCS / "ARSH_PERFUME_DOCUMENTATION.html"
pdf_path = DOCS / "ARSH_PERFUME_DOCUMENTATION.pdf"
html_path.write_text(document, encoding="utf-8")

if __name__ == "__main__":
    from playwright.sync_api import sync_playwright
    with sync_playwright() as runtime:
        browser = runtime.chromium.launch(headless=True)
        tab = browser.new_page(viewport={"width": 1000, "height": 1200}, device_scale_factor=1)
        tab.goto(html_path.as_uri(), wait_until="load")
        tab.emulate_media(media="print")
        tab.evaluate("document.fonts.ready")
        overflow = tab.evaluate("""() => Array.from(document.querySelectorAll('.sheet')).map((s,i) => {
            const b=s.querySelector('.body').getBoundingClientRect();
            const f=s.querySelector('.page-foot').getBoundingClientRect();
            return {page:i+1,title:s.querySelector('h1')?.textContent,overlap:Math.ceil(b.bottom-f.top)};
        }).filter(x=>x.page!==1 && x.overlap>0)""")
        if overflow:
            browser.close()
            raise RuntimeError("Layout overflow: " + json.dumps(overflow))
        tab.pdf(path=str(pdf_path), format="A4", print_background=True, prefer_css_page_size=True, display_header_footer=False, tagged=True, outline=True)
        preview_dir = ROOT / "storage/framework/documentation-preview"
        preview_dir.mkdir(exist_ok=True)
        for number in (1, 2, 3, 13, 21, 25, 46, len(PAGES)):
            tab.locator(f"#page-{number}").screenshot(path=str(preview_dir / f"page-{number:02}.png"))
        broken_links = tab.evaluate("""() => Array.from(document.querySelectorAll('a[href^="#"]')).filter(a => !document.getElementById(a.getAttribute('href').slice(1))).length""")
        assert broken_links == 0, "Broken table-of-contents links"
        browser.close()
    pdf_bytes = pdf_path.read_bytes()
    actual_pages = len(re.findall(rb"/Type\s*/Page\b", pdf_bytes))
    assert pdf_bytes.startswith(b"%PDF-"), "Missing PDF header"
    assert actual_pages == len(PAGES), f"Unexpected printed page count: {actual_pages}"
    assert len(definitions) == 36, "Unexpected schema table count; review documentation"
    assert b"/Outlines" in pdf_bytes and b"/StructTreeRoot" in pdf_bytes, "Missing PDF navigation or structure"
    print(json.dumps({"pdf": str(pdf_path), "html": str(html_path), "verified_pdf_pages": actual_pages, "schema_tables": len(definitions), "registered_routes": len(routes), "broken_contents_links": broken_links, "pdf_bytes": len(pdf_bytes)}, indent=2))
