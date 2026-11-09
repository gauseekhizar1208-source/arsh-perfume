"""Produce the academic report using the supplied UDP document structure.

Uses only local project sources, saved project screenshots and installed Python
packages. The original reference and the earlier technical handbook are kept.
Run: python docs/tools/build-academic-documentation.py
"""

from pathlib import Path
from datetime import date, timedelta
import base64
import html
import io
import json
import re
from PIL import Image

ROOT = Path(__file__).resolve().parents[2]
DOCS = ROOT / 'docs'
ASSETS = DOCS / 'academic-assets'
ASSETS.mkdir(exist_ok=True)
PAGES = []
MEMBERS = [('Khizar Gausee', 'D14'), ('Khuzaim Karimi', 'D24'), ('Ayan Mandli', 'D12')]


def esc(value):
    return html.escape(str(value))


def p(text):
    return '<p>' + text + '</p>'


def h(text):
    return '<h2>' + text + '</h2>'


def sub(text):
    return '<h3>' + text + '</h3>'


def ul(*items):
    return '<ul>' + ''.join('<li>' + x + '</li>' for x in items) + '</ul>'


def ol(*items):
    return '<ol>' + ''.join('<li>' + x + '</li>' for x in items) + '</ol>'


def table(headers, rows, cls=''):
    return f'<table class="{cls}"><thead><tr>' + ''.join('<th>' + x + '</th>' for x in headers) + '</tr></thead><tbody>' + ''.join('<tr>' + ''.join('<td>' + str(x) + '</td>' for x in row) + '</tr>' for row in rows) + '</tbody></table>'


def code(text):
    return '<pre>' + esc(text.strip()) + '</pre>'


def note(text):
    return '<div class="note">' + text + '</div>'


def source(*items):
    return '<p class="source">Source: ' + '; '.join(esc(x) for x in items) + '.</p>'


def page(title, body, chapter='', cls=''):
    PAGES.append(dict(title=title, body=body, chapter=chapter, cls=cls))


def svg_text(x, y, text, size=15, anchor='middle', weight='normal'):
    return f'<text x="{x}" y="{y}" text-anchor="{anchor}" font-family="Arial,sans-serif" font-size="{size}" font-weight="{weight}" fill="#243b53">{esc(text)}</text>'


def box(x, y, width, height, label, fill='#eef3f8', radius=6):
    return f'<rect x="{x}" y="{y}" width="{width}" height="{height}" rx="{radius}" fill="{fill}" stroke="#365f91" stroke-width="1.5"/>' + svg_text(x + width / 2, y + height / 2 + 5, label)


def line(points, dashed=False, arrow=True):
    return '<polyline points="' + ' '.join(f'{x},{y}' for x, y in points) + '" fill="none" stroke="#526779" stroke-width="1.5"' + (' stroke-dasharray="6 4"' if dashed else '') + (' marker-end="url(#arrow)"' if arrow else '') + '/>'


def diagram(name, width, height, content, caption):
    svg = f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {width} {height}"><defs><marker id="arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M 0 0 L 10 5 L 0 10 z" fill="#526779"/></marker></defs><rect width="100%" height="100%" fill="white"/>{content}</svg>'
    (ASSETS / f'{name}.svg').write_text(svg, encoding='utf-8')
    url = 'data:image/svg+xml;base64,' + base64.b64encode(svg.encode()).decode()
    return f'<figure class="diagram" id="{name}"><img src="{url}" alt="{esc(caption)}"><figcaption>{caption}</figcaption></figure>'


def screen(filename, caption):
    image = Image.open(ROOT / 'storage/framework' / filename).convert('RGB')
    image.thumbnail((1600, 1300))
    out = io.BytesIO()
    image.save(out, 'JPEG', quality=86)
    (ASSETS / (Path(filename).stem + '.jpg')).write_bytes(out.getvalue())
    return '<figure class="screenshot"><img alt="' + esc(caption) + '" src="data:image/jpeg;base64,' + base64.b64encode(out.getvalue()).decode() + '"><figcaption>' + caption + '</figcaption></figure>'


# The title-page fields follow the supplied template, with the user's values.
page('Title page', '''<div class="university">LJ University</div>
<div class="department">Department of Computer Applications</div>
<div class="academic-rule"></div>
<h1 class="project-name">ARSH PERFUME</h1>
<div class="project-subtitle">E-Commerce Website</div>
<div class="report-label">PROJECT DOCUMENTATION</div>
<div class="programme">MASTER OF COMPUTER APPLICATION (INTEGRATED)</div>'''
    + table(['PARTICULARS', 'DETAILS'], [
        ('GROUP MEMBERS', '<br>'.join(name for name, roll in MEMBERS)),
        ('SUBMITTED TO', 'Nirav Rajgor sir'),
        ('ACADEMIC YEAR', '2026-2027'),
        ('SUBMISSION DATE', '28-10-2026'),
        ('DIV-ROLL NO.', '<br>'.join(f'{roll} — {name}' for name, roll in MEMBERS)),
    ], 'cover-table')
    + '<div class="guide">INTERNAL GUIDE<br><strong>Nirav Rajgor sir</strong></div>', cls='cover')
page('TABLE OF CONTENTS', '', cls='toc')
page('TABLE OF CONTENTS (continued)', '', cls='toc')

page('1. INTRODUCTION', p('ARSH PERFUME is a web-based e-commerce application for presenting and selling attars, roll-on perfumes and fragrance oils. The application provides a branded customer storefront and a protected administration panel within one Laravel project. It supports product discovery, variant-level pricing and stock, shopping carts, checkout, payments, order fulfilment and customer account management.')
    + p('The website is designed for Indian delivery addresses and INR transactions. Customers can browse the catalogue without an account, while registered customers gain a wishlist, saved addresses and order history. The administrator manages the catalogue, stock, orders, content and business settings from a centralized workspace.')
    + h('1.1 Existing System')
    + p('The comparison baseline is a conventional small-store workflow in which product information is shared over messages or a basic catalogue, orders are recorded in spreadsheets, and payment and dispatch updates are checked manually. This baseline explains the problem addressed by the application; it is not a claim that a formal field survey of the store was conducted.')
    + ol('Customer asks for available fragrances, sizes and prices.', 'Store staff check stock and communicate availability.', 'Order and address details are recorded manually.', 'Payment is checked separately and the parcel is dispatched.', 'Stock sheets and customer updates are maintained independently.')
    + sub('Limitations of the comparison workflow')
    + ul('Repeated manual communication for product information and order status.', 'Separate price, stock and order records can become inconsistent.', 'Historical sales and customer information are difficult to consolidate.', 'Payment references and cancellations require careful reconciliation.')
    + source('routes/web.php', 'routes/admin.php', 'app/Services/OrderService.php'), 'Introduction')

page('1.2 Need for the New System', p('A single online platform is needed to present accurate product information, preserve purchase records and coordinate the relationship between customer orders, payments and stock. A customer should be able to discover a fragrance, choose an available size and place an order without depending on repeated manual messages.')
    + p('For the store owner, the system should provide one place to maintain prices, stock, promotional content, order status and reporting. Payment states and fulfilment states must remain distinct so that a customer-entered transaction reference or a browser redirect cannot be mistaken for confirmed receipt of money.')
    + h('1.3 Objective of the New System')
    + ul('Provide a searchable, responsive catalogue with categories, fragrance filters and product details.', 'Maintain price, sale price, availability and low-stock thresholds for each product variant.', 'Support guest and signed-in carts, coupons and validated checkout.', 'Calculate totals on the server using shared shipping, discount and tax rules.', 'Support COD, verified Razorpay payments and direct UPI with manual bank-receipt verification.', 'Reserve stock during order placement and restore cancelled reservations once.', 'Allow customers to manage their accounts, addresses, wishlist and own orders.', 'Provide an administrator workspace for products, inventory, customers, content and reports.', 'Maintain order history, stock movements and an administrator activity trail.', 'Document configuration, testing, known limitations and operation for future maintenance.')
    + source('app/Services/', 'app/Models/', 'config/arsh.php'), 'Introduction')

page('1.4 Problem Definition', p('Develop a maintainable e-commerce website for ARSH PERFUME that connects product browsing, variant selection, cart management, validated checkout, payment handling, stock reservation and order fulfilment. The application must prevent unauthorized access to customer records, avoid trusting browser-submitted totals and preserve the purchased item and address details as historical snapshots.')
    + p('The scope includes a public storefront, customer account area and administrator panel. The source does not implement a marketplace for multiple sellers, a machine-learning recommendation service or a separate mobile application.')
    + h('1.5 Core Components')
    + table(['Module', 'Responsibility'], [
        ('User management', 'Email/password accounts, password reset, active-account checks and optional Google sign-in.'),
        ('Catalogue', 'Categories, product descriptions, images, fragrance details and purchasable sizes.'),
        ('Cart and checkout', 'Guest/customer carts, coupon validation, Indian addresses and order placement.'),
        ('Payment management', 'COD, signed Razorpay callbacks/webhooks and manually verified direct UPI.'),
        ('Inventory', 'Variant stock, reservation, adjustments and movement history.'),
        ('Order management', 'Status transitions, tracking, invoices, cancellations, returns and refunds.'),
        ('Customer engagement', 'Wishlist, verified-purchase reviews, contact enquiries, FAQs and newsletter.'),
        ('Administration and reporting', 'Dashboard, business settings, CMS, reports, CSV exports and activity logs.'),
    ])
    + source('routes/', 'app/Http/Controllers/', 'app/Services/'), 'Introduction')

page('1.6 Project Profile', table(['Attribute', 'Details'], [
        ('Project name', 'ARSH PERFUME — E-Commerce Website'), ('Project type', 'Web-based retail e-commerce application'),
        ('Academic programme', 'Master of Computer Application (Integrated)'), ('Academic year', '2026-2027'),
        ('Team size', '3 students'), ('Internal guide / submitted to', 'Nirav Rajgor sir'),
        ('Backend', 'PHP ^8.2, Laravel ^12.0 and Eloquent ORM'), ('Frontend', 'Blade, Tailwind CSS 4, Alpine.js 3 and Vite 7'),
        ('Database', 'MySQL-oriented schema; SQLite used by automated tests'), ('Payments', 'Razorpay, COD and direct UPI QR'),
        ('Users', 'Visitors/guests, registered customers and administrators'), ('Development environment', 'Windows / XAMPP project workspace'),
        ('Submission date', '28-10-2026'),
    ])
    + h('1.7 Assumptions and Constraints')
    + sub('Assumptions')
    + ul('The owner supplies accurate product claims, images, prices, stock and business policies.', 'Customers provide valid contact and delivery information.', 'Administrators verify UPI receipts against the actual receiving account.', 'The deployed server has a working database, storage, mail configuration and scheduled background processes.')
    + sub('Constraints')
    + p('Checkout uses INR and configured Indian states/territories with six-digit PIN validation. Online integrations depend on provider credentials and connectivity. Direct UPI requires administrator review. Catalogue and policy seeds contain intentionally incomplete/unpublished content.')
    + source('composer.json', 'package.json', 'app/Http/Requests/CheckoutRequest.php', 'database/seeders/'), 'Introduction')

page('1.8 Advantages and Limitations', sub('Advantages')
    + ul('A centralized product catalogue and administration workspace reduce repeated data entry.', 'Variant-level pricing and stock support different fragrance sizes.', 'Server-side total calculation keeps cart, checkout and order amounts consistent.', 'Transactional placement and row locking protect stock during competing checkouts.', 'Item, address and receiving-account snapshots preserve purchase-time information.', 'Signed payment verification and duplicate-event handling support payment integrity.', 'Role checks and ownership policies protect administration and customer records.', 'Reports, history and movement logs support operational review.', 'Responsive layouts support desktop and mobile browser use.')
    + sub('Current limitations')
    + ul('Direct UPI and UPI refunds involve external bank verification or transfer.', 'CSV product export works; the product-import route lacks its controller implementation.', 'Category ordering uses numeric sort values rather than drag-and-drop interaction.', 'SMS/WhatsApp notifications, multiple currencies and multilingual content are not implemented.', 'The automated suite currently contains two existing failures, described in Section 5.7.', 'A live gateway transaction and a real Google consent round trip require configured credentials and separate verification.', 'No measured production-load or uptime guarantee is established by this report.')
    + note('This report describes the implemented source and observed checks. Proposed enhancements and planning allocations are clearly distinguished from verified functionality.'), 'Introduction')

page('2. PROJECT PLANNING', h('2.1 Team Details and Roles')
    + table(['Name of the Student', 'Div / Roll No.', 'Proposed Role of the Student'], [
        ('Khizar Gausee', 'D14', 'Frontend implementation, responsive screens and customer usability.'),
        ('Khuzaim Karimi', 'D24', 'Backend services, authentication, checkout and payment integration.'),
        ('Ayan Mandli', 'D12', 'Database/inventory design, testing and project documentation.'),
    ], 'team')
    + note('The member names and roll entries are supplied by the project group. The responsibility split is a proposed planning allocation for this report; it is not presented as a verified contribution history.')
    + sub('Coordination and review plan')
    + ul('Review requirements against the implemented routes, services and database migrations.', 'Agree on interfaces between catalogue, cart, pricing, orders, payments and inventory.', 'Keep customer-facing and administrator changes reviewable and consistent.', 'Review access checks, stock effects and payment outcomes before accepting a change.', 'Maintain screenshots and documentation when screens or configuration change.')
    + sub('Expected deliverables')
    + ul('Working project source and database migrations.', 'Public storefront, customer account flows and administrator modules.', 'Local setup, environment configuration and operating instructions.', 'Automated test evidence with unresolved issues recorded.', 'Academic report, diagrams, data dictionary and implementation screenshots.')
    + p('The next page gives the proposed twelve-week schedule requested by the reference format. It is an academic planning model ending on the supplied submission date, not an assertion of actual work dates.'), 'Project Planning')

activities = [
    ('Requirement analysis', 1, 2), ('System design', 2, 3), ('Database design', 3, 4), ('UI / responsive frontend', 3, 6),
    ('Backend / authentication', 4, 7), ('Payments / inventory', 6, 9), ('Integration and testing', 8, 11),
    ('Documentation', 6, 12), ('Final review / presentation', 11, 12),
]
gantt_rows = []
for name, first, last in activities:
    gantt_rows.append([name] + ['<span class="bar">●</span>' if first <= week <= last else '' for week in range(1, 13)])
page('2.2 Proposed Timeline (Gantt Chart)', p('Illustrative twelve-week plan: 06-08-2026 to 28-10-2026. Week numbers represent planned work windows; overlapping activities permit integration and documentation alongside implementation.')
    + table(['Activity'] + [f'W{i}' for i in range(1, 13)], gantt_rows, 'gantt')
    + sub('Planned milestones')
    + ul('Week 2: requirements baseline and user/scope review.', 'Week 4: system/database design baseline.', 'Weeks 7–9: core workflows and payment/inventory integration.', 'Weeks 11–12: verification, report review and submission on 28-10-2026.')
    + sub('Risk and dependency planning')
    + p('Payment-provider activation, Google credentials, owner-supplied catalogue content and server configuration can affect readiness. Keep local simulation and mock-based tests available, while recording which real integrations remain unverified. Reserve time for test failures, publication review and documentation correction.'), 'Project Planning')

page('3. REQUIREMENT DETERMINATION & ANALYSIS', h('3.1 Requirement Determination')
    + p('The requirements below are derived from the repository, validators, routes, service rules, migration definitions and existing test cases. No interviews, customer surveys or benchmark measurements are claimed. Requirements describe observable behavior and the checks needed to accept it.')
    + table(['ID', 'Functional requirement', 'Acceptance condition'], [
        ('FR01', 'Browse and search the catalogue', 'Active catalogue pages support keyword, category, family, price and stock filters.'),
        ('FR02', 'Manage product variants', 'Each size carries a unique SKU, price, active state and stock.'),
        ('FR03', 'Authenticate users', 'Email/password and configured Google sign-in preserve safe customer roles.'),
        ('FR04', 'Maintain cart contents', 'Guest/customer carts validate quantities and merge on sign-in.'),
        ('FR05', 'Apply coupons', 'Dates, eligibility, minimums, usage limits and discount caps are checked.'),
        ('FR06', 'Place orders safely', 'Checkout rechecks price/stock in a transaction and snapshots purchase data.'),
        ('FR07', 'Handle payments', 'COD, verified Razorpay and manually verified UPI follow their respective rules.'),
        ('FR08', 'Track and cancel orders', 'Customers see their permitted orders; cancellation respects status rules.'),
        ('FR09', 'Control inventory', 'Stock changes create movement records and do not become negative.'),
        ('FR10', 'Administer the store', 'Active administrators manage catalogue, content, orders and settings.'),
        ('FR11', 'Support engagement', 'Wishlist, delivered-purchase reviews, contact and newsletter flows exist.'),
        ('FR12', 'Report and audit', 'Dashboard/reports, order histories and admin actions support review.'),
    ], 'requirements')
    + source('routes/', 'app/Http/Requests/', 'app/Services/', 'tests/Feature/'), 'Requirements')

page('3.1 Requirement Determination (continued)', sub('Non-Functional Requirements')
    + table(['Area', 'Requirement / design expectation', 'Evidence or practical limit'], [
        ('Security', 'Protect roles, ownership, sessions, input and payment verification.', 'Middleware, policies, request validation, signatures and security tests.'),
        ('Data integrity', 'Prevent duplicate orders/events and inconsistent stock/totals.', 'Idempotency keys, transactions, row locks and movement records.'),
        ('Usability', 'Provide clear navigation, validation and responsive screens.', 'Blade layouts, customer/admin views and saved preview screenshots.'),
        ('Maintainability', 'Separate request, business and persistence responsibilities.', 'Controllers, Form Requests, services, models and reusable helpers.'),
        ('Performance', 'Use efficient queries, built assets and cached configuration/settings.', 'Implementation mechanisms exist; no load-tested response-time target is claimed.'),
        ('Reliability', 'Retain purchase history and run queued/scheduled work consistently.', 'Snapshots, histories, queue worker and scheduler; deployment operation is required.'),
        ('Recoverability', 'Back up the database/media and rehearse restoration.', 'Operating requirement; this report does not claim a completed restore rehearsal.'),
        ('Compatibility', 'Support current desktop/mobile browser workflows.', 'Responsive implementation; broad browser-matrix testing remains a deployment task.'),
    ])
    + sub('Hardware and software requirements')
    + p('Development requires a machine capable of running PHP, MySQL, Composer, Node/npm and a modern browser. The project workspace uses Windows/XAMPP. No formal minimum RAM/CPU specification is asserted without measurement. Use PHP 8.2+, MySQL 8 and Node compatible with Vite 7 (20.19+ or 22.12+), with appropriate PHP database, file, XML and image extensions.')
    + source('composer.json', 'package.json', 'phpunit.xml', 'config/'), 'Requirements')

page('3.2 Targeted Users', table(['User Type', 'Role', 'Responsibilities / permissions'], [
        ('Visitor / guest', 'Prospective buyer', 'Browse, search, manage a session cart, use enabled guest checkout, track by order number/email and contact the store.'),
        ('Registered customer', 'Account holder', 'Manage profile/password, addresses, wishlist and own order history; cancel permitted orders and review delivered purchases.'),
        ('Administrator', 'Store operator', 'Manage catalogue, images, inventory, orders, payment confirmation, content, customers, settings and reports.'),
        ('Payment provider', 'External service actor', 'Provide Razorpay checkout/payment records and signed event notifications.'),
        ('Google identity provider', 'Optional external service actor', 'Return a verified identity through the configured OAuth flow.'),
    ])
    + sub('Role boundaries')
    + p('Only active administrators may enter /admin. Public registration cannot grant an administrator role. Customers cannot access another customer’s orders or addresses. A guest order is accessible through its permitted placing-session context; tracking uses the separate order-number/email lookup.')
    + sub('User journey')
    + ol('Discover products through home, shop or collections.', 'Choose an available variant and build the cart.', 'Review coupon, shipping and tax calculations.', 'Provide delivery information and choose a payment method.', 'Complete payment instructions and retain the order number.', 'Track fulfilment; administrators process verified orders.')
    + source('bootstrap/app.php', 'app/Support/OrderAccess.php', 'app/Policies/', 'routes/auth.php'), 'Requirements')

# Use-case diagram: actors remain outside the system boundary.
content = '<rect x="160" y="25" width="520" height="570" rx="8" fill="#f9fbfd" stroke="#365f91"/>' + svg_text(420, 52, 'ARSH PERFUME SYSTEM', 18, weight='bold')
def actor(x, y, name):
    return f'<circle cx="{x}" cy="{y}" r="13" fill="white" stroke="#243b53"/><path d="M{x} {y+13}v36m-25-21h50m-25 21l-22 28m22-28l22 28" fill="none" stroke="#243b53" stroke-width="2"/>' + svg_text(x, y + 99, name, 14)
content += actor(70, 100, 'Visitor') + actor(70, 340, 'Customer') + actor(770, 150, 'Administrator') + actor(770, 420, 'Razorpay')
cases = [(300,105,'Browse / search'),(535,105,'Manage catalogue'),(300,195,'Cart / checkout'),(535,195,'Manage inventory'),(300,285,'Account / wishlist'),(535,285,'Process orders'),(300,375,'Track / cancel own order'),(535,375,'Verify UPI receipt'),(300,465,'Submit purchase review'),(535,465,'Content / reports'),(420,550,'Verify online payment')]
for x,y,label in cases:
    content += f'<ellipse cx="{x}" cy="{y}" rx="113" ry="28" fill="white" stroke="#58779d"/>' + svg_text(x,y+5,label,13)
for a,b in [((98,140),(187,105)),((98,150),(187,195)),((98,380),(187,195)),((98,385),(187,285)),((98,390),(187,375)),((98,400),(187,465)),((742,190),(648,105)),((742,200),(648,195)),((742,210),(648,285)),((742,220),(648,375)),((742,230),(648,465)),((742,465),(533,550))]:
    content += line([a,b],arrow=False)
page('4. SYSTEM DESIGN', h('4.1 Use Case Diagram')
    + p('The system boundary separates public shopping and customer-account actions from administrator operations. Razorpay is an external payment actor. Google OAuth is an optional identity integration described in the development chapter.')
    + diagram('use-case', 840, 625, content, 'Figure 4.1: Main actors and use cases of ARSH PERFUME.')
    + note('Order ownership, customer cancellation states, review eligibility and administrator payment verification apply to the associated use cases.'), 'System Design')

# Domain class diagram with actual Eloquent entity attributes and cardinalities.
content = ''
classes = [
    (20,30,'Category',['id, name, slug','is_active, sort_order']),
    (20,220,'Product',['id, category_id, sku','name, status, slug']),
    (20,420,'ProductVariant',['id, product_id, sku','price, sale_price, stock']),
    (325,30,'User',['id, role_id, email','is_active, google_id']),
    (325,220,'Order',['id, user_id, order_number','status, payment_status','grand_total, refunded_amount']),
    (325,420,'OrderItem',['id, order_id','product_variant_id, quantity','unit_price (snapshot)']),
    (630,220,'Payment',['id, order_id, provider','status, amount, meta']),
    (630,420,'OrderStatusHistory',['id, order_id','from_status, to_status','changed_by, note']),
]
for x,y,name,fields in classes:
    content += f'<rect x="{x}" y="{y}" width="240" height="120" fill="white" stroke="#365f91"/><rect x="{x}" y="{y}" width="240" height="34" fill="#e6eef7"/>' + svg_text(x+120,y+23,name,16,weight='bold')
    for i,field in enumerate(fields): content += svg_text(x+12,y+58+i*22,field,13,'start')
for points in [[(140,150),(140,220)],[(140,340),(140,420)],[(445,150),(445,220)],[(565,280),(630,280)],[(445,340),(445,420)],[(260,480),(325,480)],[(565,320),(610,320),(610,400),(750,400),(750,420)]]:
    content += line(points,arrow=False)
for x,y,label in [(161,175,'0..1'),(155,210,'*'),(155,366,'1'),(155,407,'*'),(466,175,'0..1'),(460,210,'*'),(583,270,'1'),(615,270,'*'),(460,365,'1'),(460,408,'*'),(286,470,'0..1'),(311,470,'*'),(580,340,'1'),(765,410,'*')]: content += svg_text(x,y,label,14)
page('4.2 Class Diagram', p('The diagram shows central Eloquent classes and persisted attributes. Multiplicity 1–* denotes a one-to-many association; 0..1 denotes an optional association. Order items retain snapshots, so later product edits do not rewrite the original sale.')
    + diagram('class-diagram', 895, 565, content, 'Figure 4.2: Core class relationships and cardinalities.')
    + sub('Additional domain classes')
    + p('Role, UserAddress, Cart/CartItem, Wishlist/WishlistItem, Coupon/CouponUsage, InventoryMovement, Review, Page, Banner, Faq and Setting support the remaining modules. Price and stock belong to ProductVariant. Guest orders have a nullable user_id. The full table definitions follow in Section 4.5.')
    + source('app/Models/', 'database/migrations/'), 'System Design')

# Checkout sequence diagram: payment occurs after the placement transaction.
columns = [(75,'Customer'),(260,'Controller'),(455,'OrderService'),(655,'Database'),(840,'Payment flow')]
content = ''
for x,label in columns:
    content += box(x-66,20,132,40,label) + line([(x,60),(x,610)],dashed=True,arrow=False)
messages = [(75,260,105,'1. Submit checkout'),(260,455,160,'2. place(cart, input)'),(455,655,215,'3. Begin / lock / recheck'),(455,655,275,'4. Save order + items'),(455,655,330,'5. Reserve stock / commit'),(455,260,390,'6. Return saved order'),(260,75,445,'7. Show payment / success'),(75,840,500,'8. COD / Razorpay / UPI'),(840,655,560,'9. Record verified outcome')]
for start,end,y,label in messages:
    content += line([(start,y),(end,y)]) + svg_text((start+end)/2,y-10,label,13)
page('4.3 Interaction Diagram', p('This sequence summarizes checkout interaction. The OrderService transaction validates and records the order before method-specific payment handling. Razorpay confirmation uses verified signatures and amounts; direct UPI requires actual receipt verification by an administrator.')
    + diagram('interaction-diagram', 925, 640, content, 'Figure 4.3: Checkout interaction / sequence diagram.')
    + note('A repeated idempotency key returns the existing order. A browser redirect or submitted UPI reference does not independently establish that payment was received.'), 'System Design')

# Activity diagram with decision branches and explicit stock reservation.
content = '<circle cx="430" cy="22" r="10" fill="#243b53"/>'
for y,label in [(55,'Browse product → choose variant'),(120,'Add to cart → review totals'),(185,'Enter shipping details / choose payment')]:
    content += box(240,y,380,42,label)
content += line([(430,32),(430,55)]) + line([(430,97),(430,120)]) + line([(430,162),(430,185)])
content += '<polygon points="430,249 560,290 430,331 300,290" fill="#f3f6fb" stroke="#365f91"/>' + svg_text(430,285,'Stock / input',14) + svg_text(430,305,'valid?',14)
content += line([(430,227),(430,249)]) + line([(300,290),(120,290),(120,206),(240,206)]) + svg_text(190,280,'No: correct inputs',13)
content += line([(430,331),(430,355)]) + svg_text(460,347,'Yes',13) + box(220,355,420,46,'Create order + snapshots + reserve stock')
content += line([(430,401),(430,425)]) + box(260,425,340,42,'Follow selected payment method')
content += line([(430,467),(430,495)])
content += '<polygon points="430,495 555,535 430,575 305,535" fill="#f3f6fb" stroke="#365f91"/>' + svg_text(430,540,'Payment method?',14)
for x,label in [(90,'COD: no prepayment'),(335,'Razorpay: verify'),(580,'UPI: bank review')]: content += box(x,620,200,42,label)
content += line([(305,535),(190,535),(190,620)]) + line([(430,575),(435,620)]) + line([(555,535),(680,535),(680,620)])
content += line([(190,662),(190,695),(435,695)]) + line([(435,662),(435,715)]) + line([(680,662),(680,695),(435,695)])
content += box(260,715,350,42,'Confirm → process → ship → deliver') + line([(435,757),(435,784)]) + '<circle cx="435" cy="797" r="11" fill="white" stroke="#243b53" stroke-width="2"/><circle cx="435" cy="797" r="6" fill="#243b53"/>'
page('4.4 Activity Diagram', diagram('activity-diagram', 870, 825, content, 'Figure 4.4: Main shopping and fulfilment activity flow.')
    + p('Unpaid online orders cannot advance through fulfilment. COD collection can be recorded explicitly and is automatically marked on delivery. Cancellation follows allowed state transitions and restores a stock reservation once.'), 'System Design', 'activity')

page('4.5 Data Dictionary', p('The migration source declares 36 tables: 29 application tables and 7 Laravel support tables. Laravel also creates its migrations bookkeeping table. The following dictionary reproduces field declarations, constraints, defaults, indexes and relationships from the maintained migration-derived reference; it contains no customer records or credentials.')
    + table(['Domain', 'Tables'], [
        ('Accounts', 'roles, users, user_addresses'), ('Catalogue', 'categories, products, product_images, product_variants, reviews'),
        ('Shopping', 'carts, cart_items, wishlists, wishlist_items'), ('Coupons', 'coupons, coupon_products, coupon_categories, coupon_usages'),
        ('Fulfilment', 'orders, order_items, order_status_histories, inventory_movements'), ('Payments', 'payments, payment_webhook_events'),
        ('Content / operations', 'contact_enquiries, newsletter_subscribers, banners, pages, faqs, settings, admin_activity_logs'),
        ('Framework support', 'password_reset_tokens, sessions, cache, cache_locks, jobs, job_batches, failed_jobs'),
    ])
    + sub('Reading the schema declarations')
    + ul('<code>id()</code>: auto-incrementing primary key. <code>foreignId()</code>: related record identifier.', '<code>nullable()</code>: optional value. <code>unique()</code>: uniqueness constraint.', '<code>decimal(name, precision, scale)</code>: fixed-precision decimal value.', '<code>timestamps()</code>: created_at and updated_at. <code>softDeletes()</code>: deleted_at.', '<code>constrained()</code> and deletion rules identify foreign-key behavior.')
    + p('Money is stored as decimal database values and calculated in integer paise for checkout. Order item/address and UPI receiving-account snapshots preserve purchase-time information. The users table includes Google identity/password-login additions from the October 2026 migration.')
    + source('database/migrations/', 'docs/DATABASE_REFERENCE.md'), 'System Design')

dictionary = (DOCS / 'DATABASE_REFERENCE.md').read_text(encoding='utf-8')
definitions = []
for block in re.split(r'(?m)^## ', dictionary)[1:]:
    name, _, body = block.partition('\n')
    if name == 'Tables': continue
    rows = []
    for text in body.splitlines():
        if text.startswith('| ') and not text.startswith('| Column'):
            cells = [x.strip().replace('`','') for x in text.strip('|').split('|')]
            if len(cells) == 2: rows.append(tuple(esc(x) for x in cells))
    extra = re.search(r'(?m)^Additional indexes/constraints: (.*)$', body)
    if extra: rows.append(('Indexes / constraints', esc(extra[1].replace('`',''))))
    if rows: definitions.append((name.strip(), rows))

units = []
for name, rows in definitions:
    for offset in range(0, len(rows), 22):
        part = rows[offset:offset+22]
        cost = sum(max(1, (len(re.sub('<[^>]+>','', row[1])) + 85)//86) for row in part) + 5
        units.append((name + (' (continued)' if offset else ''), part, cost))
pending, used, index = [], 0, 1
for name, rows, cost in units:
    if pending and used + cost > 38:
        page(f'4.5 Data Dictionary (continued {index})', ''.join(pending), 'System Design', 'dictionary')
        index += 1
        pending, used = [], 0
    pending.append(h(name) + table(['Field(s)', 'Type, constraints and defaults'], rows, 'schema'))
    used += cost
if pending: page(f'4.5 Data Dictionary (continued {index})', ''.join(pending), 'System Design', 'dictionary')

page('5. DEVELOPMENT', h('5.1 Development Approach')
    + p('The implementation uses a layered, server-rendered Laravel architecture. Controllers coordinate requests, Form Requests validate structured input, services implement business operations, Eloquent models represent persistence, and Blade views render the interface. Browser libraries add local interactions, motion, QR generation and dashboard charts.')
    + p('The repository demonstrates modular implementation and automated checks. The available evidence does not establish a historical sprint cadence, daily meetings or individual commit contributions. The staged process below is a practical description of how the modules are organized and should be maintained.')
    + ol('Identify users, product information, payment methods and order rules.', 'Design the database and relationships through versioned migrations.', 'Implement public discovery, authentication and customer account screens.', 'Implement shared cart, pricing, coupon, order and inventory services.', 'Add verified payment flows, administration, content and reports.', 'Exercise isolated tests and preview screens; record gaps before release.')
    + sub('Important implementation decisions')
    + ul('Prices and stock belong to product variants rather than only the parent product.', 'Checkout runs within a transaction, locks variants in stable order and revalidates current price/stock.', 'Order placement reserves stock immediately and records purchase-time snapshots.', 'Payment and fulfilment states are independent.', 'Runtime business settings use the settings table; secrets remain in server environment configuration.')
    + source('app/Http/', 'app/Services/', 'app/Models/', 'resources/views/'), 'Development')

page('5.2 Coding Standards', table(['Element', 'Convention used in this Laravel project'], [
        ('Classes', 'PascalCase: OrderService, ProductVariant, CheckoutController.'), ('Methods / variables', 'camelCase: calculate(), verifyReceipt(), orderNumber.'),
        ('Constants', 'UPPER_SNAKE_CASE: STATUS_ACTIVE, REFERENCE_RULE.'), ('Database tables / columns', 'Plural snake_case tables; snake_case columns and foreign keys.'),
        ('Routes', 'Named routes; public slugs and human order numbers where configured.'), ('Indentation', 'Consistent PHP indentation, generally four spaces.'),
        ('Templates', 'Blade layouts/components with escaped output and CSRF-protected forms.'), ('Business logic', 'Shared services for order, pricing, inventory and payment rules.'),
    ])
    + sub('Validation and error handling')
    + p('Use CheckoutRequest and ProductRequest for structured validation, with controller/service checks for method availability, ownership and state transitions. Keep failures meaningful to the user without exposing server secrets. The application includes dedicated 403, 404, 419, 429, 500 and 503 error views.')
    + sub('Authentication and sensitive data')
    + p('Passwords use Laravel hashing rather than plain text or a generic SHA-256 password scheme. Google OAuth linking requires the explicit account flow; matching an existing email alone does not silently link accounts. Uploaded raster images are type/size/dimension validated and stored under random names.')
    + sub('Money and transactions')
    + p('Use Money conversions and PricingService instead of duplicating floating-point total calculations in templates or controllers. Apply inventory mutations through InventoryService and retain order status and stock movement history.')
    + source('app/Support/Money.php', 'app/Http/Requests/', 'app/Services/', 'config/hashing.php'), 'Development')

page('5.3 GitHub Repository Details', table(['Item', 'Verified project information'], [
        ('Local project directory', 'C:\\xampp\\htdocs\\arsh-perfume'), ('Application package', 'arshperfume/store'),
        ('Source control', 'A local .git directory exists.'), ('GitHub repository URL', 'No configured remote was found during inspection; a public/private repository URL is not supplied.'),
        ('Contribution history', 'No verified commit-based contribution history is asserted in this report.'), ('Dependency versions', 'composer.lock and package-lock.json are present for reproducible installation.'),
    ])
    + sub('Recommended repository organization')
    + code('''app/                  Controllers, services, models and policies
bootstrap/            Application boot and middleware
config/               Framework and business configuration
database/             Migrations, factories and seeders
resources/            Blade templates, CSS and JavaScript
routes/               Public, auth, admin and scheduled routes
tests/                Unit and feature tests
docs/                 Project documentation and generators
public/               Web document root and built assets''')
    + sub('Version-control handover')
    + p('When a repository is configured, keep reviewable commits, document the branching approach actually chosen and exclude .env, credentials, runtime logs and private customer data. Do not insert an invented GitHub URL or fabricated commit history into an academic submission.')
    + source('composer.json', '.gitignore', 'composer.lock', 'package-lock.json'), 'Development')

page('5.4 AI Tools / Libraries / APIs Used', table(['Tool / dependency', 'Version family', 'Purpose in this project'], [
        ('PHP / Laravel', 'PHP ^8.2 / Laravel ^12.0', 'Application runtime, routing, validation, sessions, ORM, queues and notifications.'),
        ('Laravel Socialite', '^5.31', 'Google OAuth identity redirect/callback and profile mapping.'),
        ('Tailwind CSS / Vite', '4 / 7', 'CSS build pipeline, browser modules and production assets.'),
        ('Alpine.js', '^3.14.9', 'Lightweight browser interactions in server-rendered screens.'),
        ('Chart.js', '^4.4.9', 'Administrator charts and report visualization.'),
        ('Framer Motion', '^14.6.0', 'DOM-based storefront motion without a React SPA.'),
        ('qrcode', '^1.5.4', 'Generate UPI QR codes locally in the browser.'),
        ('Axios', '^1.11.0', 'HTTP utility available to browser modules.'),
        ('Razorpay API', 'Configured provider API', 'Payment order/capture verification and eligible live refunds.'),
        ('Google OAuth', 'Configured web OAuth client', 'Optional customer/admin account sign-in and explicit linking.'),
        ('PHPUnit', '^11.5', 'Unit and feature verification with in-memory SQLite.'),
    ], 'libraries')
    + sub('AI usage disclosure')
    + p('The application source does not contain an integrated AI prediction, chatbot or LLM API feature. Codex assisted with source inspection and preparation of this documentation. No unsupported claim about the original team’s use of other AI tools is made.')
    + note('Versions above are the dependency constraints declared in this project, not a claim that they are the newest available releases.')
    + source('composer.json', 'package.json', 'app/Services/RazorpayService.php', 'config/services.php'), 'Development')

page('5.5 Screenshots & Implementation Details', h('Screenshot 1: Storefront / Home')
    + screen('storefront-1440.png', 'Figure 5.1: Saved desktop preview of the ARSH PERFUME home screen.')
    + p('The homepage presents the fragrance-house identity, primary navigation and collection discovery. The visual product sculpture is a brand illustration; actual catalogue photography is maintained separately.')
    + ul('Blade layouts render navigation, page sections and store settings.', 'Storefront CSS and browser motion enhance the branded presentation.', 'The saved capture displays the local simulated-payment notice.')
    + note('Screenshots in this section are existing project preview captures. They document the interface, not current live stock, real sales totals or successful provider transactions.'), 'Development', 'screens')

page('5.5 Screenshots (continued)', h('Screenshot 2: Catalogue and Search')
    + screen('service-shop-1440.png', 'Figure 5.2: Saved desktop catalogue preview with search/filter controls.')
    + p('The catalogue allows keyword search and filtering by category, fragrance family, price and stock availability. Product cards show available catalogue content; variant pricing and stock determine whether a size can be purchased.')
    + ul('ShopController validates allowed query filters and sort values.', 'Eloquent catalogue scopes load visible products and variants.', 'The cart and checkout revalidate current stock and price independently of the displayed card.')
    + source('app/Http/Controllers/ShopController.php', 'resources/views/pages/shop.blade.php'), 'Development', 'screens')

page('5.5 Screenshots (continued)', h('Screenshot 3: Administrator Dashboard')
    + screen('admin-1440.png', 'Figure 5.3: Saved administrator workspace preview; displayed figures are capture-time values.')
    + p('The administrator dashboard groups order/revenue information, catalogue counts, charting and stock attention indicators. Navigation opens order, product, inventory, customer, report and content modules.')
    + ul('The admin route group requires authentication and an active administrator.', 'ReportService supplies shared metrics; Chart.js renders charts.', 'Revenue excludes cancelled/returned orders and nets recorded refunds.')
    + source('app/Http/Controllers/Admin/DashboardController.php', 'app/Services/ReportService.php'), 'Development', 'screens')

page('5.5 Implementation Details (continued)', sub('Authentication and customer access')
    + p('Email registration/login, password reset and optional Google sign-in are implemented through dedicated controllers. My Account supports profile, password and address changes. Ownership policies protect customer order and address actions. Disabled users are signed out by active-account middleware.')
    + sub('Checkout, payments and stock')
    + p('OrderService rechecks stock and prices, locks variants in stable ID order, validates coupons, saves purchase snapshots and reserves stock in a transaction. COD collection is recorded separately. Razorpay uses server totals, signed callbacks/webhooks and duplicate-event handling. UPI references remain unverified until an administrator checks actual receipt.')
    + sub('Fulfilment and content')
    + p('The configured order lifecycle is pending → confirmed → processing → shipped → out for delivery → delivered, with permitted cancellation and return branches. Administrators can add tracking information, view invoices and record refunds. Pages, FAQs, banners and homepage settings provide content management.')
    + table(['Implementation rule', 'Result'], [
        ('Customer cancellation', 'Allowed for own pending/confirmed orders only.'), ('Stock restoration', 'Cancellation restores reserved stock once.'),
        ('Razorpay timeout', 'Unpaid reservations older than 30 minutes are released by the scheduled task.'), ('UPI timeout', 'Direct UPI awaiting review is excluded; review/cancel manually.'),
        ('UPI refund', 'Bank transfer happens outside the store before the refund record is entered.'),
    ])
    + source('config/arsh.php', 'app/Services/OrderService.php', 'app/Services/UpiPaymentService.php'), 'Development')

page('5.6 Installation and Configuration', p('This additional section supplies the setup information needed to reproduce the submitted project. The commands describe a fresh local installation; preserve an existing environment file, application key and owner data when working with an installed store.')
    + code('''Set-Location C:\\xampp\\htdocs\\arsh-perfume
composer install
# Only if .env does not already exist:
Copy-Item .env.example .env
php artisan key:generate
# Create the MySQL database and set DB_* in .env.
php artisan migrate --seed
npm ci
npm run build
php artisan storage:link
php artisan arsh:create-admin owner@example.com --name="Store Owner"
php artisan serve''')
    + p('The administrator command prompts for a password. Use the address actually opened in the browser as APP_URL. For Apache/XAMPP, point the virtual-host document root at public/ and enable rewriting. Ensure storage/ and bootstrap/cache/ are writable by the web process.')
    + sub('Configuration groups')
    + table(['Configuration', 'Where it belongs'], [
        ('Database, application key, mail and payment secrets', 'Server .env / deployment environment; never in frontend code.'),
        ('Google OAuth client and callback', 'Server configuration; callback must match scheme, hostname, port and path.'),
        ('Business identity, shipping/tax, COD, guest checkout and UPI payee', 'Admin → Settings; reviewed by the store owner.'),
        ('Initial product/policy content', 'Draft/unpublished seeds; owner confirms content before publication.'),
    ])
    + source('.env.example', 'composer.json', 'config/arsh.php', 'docs/google-login-setup.md'), 'Development')

page('5.7 Testing and Validation', p('The documented isolated test run on 27-10-2026 used the testing environment and in-memory SQLite. The existing automated suite returned <strong>68 passed, 2 failed and 389 assertions</strong>. This is the observed result, not a claim that every deployment or live integration has passed.')
    + table(['Test area', 'Representative coverage'], [
        ('Money / pricing', 'Paise conversion, monetary formatting and checkout totals.'), ('Access control', 'Admin/customer separation, ownership and guest access.'),
        ('Catalogue / stock', 'Publishing gates, sale price rules, stock adjustments and movement records.'), ('Checkout / orders', 'Availability, coupon validation, price snapshots, status transitions and one-time stock restoration.'),
        ('Payments', 'Forged signatures, captures, duplicate events, amount mismatch and UPI receipt verification.'), ('Google / rendering', 'Account linking, role protection, OAuth state and storefront/admin rendering.'),
    ])
    + sub('Existing failures recorded')
    + ol('AdminProductTest image-storage test: CLI PHP lacks GD, so the fixture image cannot be generated.', 'PaymentWebhookTest wrong-amount test: the payment-attempt assertion expects pending, while the implementation retains created. The order itself remains pending.')
    + sub('Checks still required before production')
    + p('Resolve the two failures and rerun the suite. Exercise the real configured Razorpay/Google paths, mail delivery, scheduler/worker operation, browser flows and backup restoration. A real bank transfer, live gateway charge/refund and production-load benchmark are not claimed here.')
    + code('php artisan test --compact\nphp artisan route:list\nnpm run build')
    + source('phpunit.xml', 'tests/Unit/', 'tests/Feature/'), 'Development')

page('5.8 Deployment and Maintenance', ol('Deploy PHP/MySQL with HTTPS and public/ as the web document root.', 'Set production environment, disable debug output and use secure session cookies.', 'Install locked dependencies, build assets, back up data and apply versioned migrations.', 'Verify uploaded-media access and cache application configuration/routes/views.', 'Run a supervised queue worker and invoke the scheduler every minute.', 'Confirm products, policies, taxes, shipping, notifications and payment methods before opening the store.')
    + code('''composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
# Persistent worker:
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
# Run once per minute through the server scheduler:
php artisan schedule:run''')
    + sub('Operational responsibilities')
    + ul('Inspect application logs and failed jobs; maintain backups of the database and uploaded media.', 'Verify restore procedures and retain protected deployment configuration.', 'Review stale UPI orders manually and verify receipts before confirmation.', 'Check scheduled unpaid Razorpay expiry and daily failed-job pruning.', 'Update the report when schema, interfaces, integrations or validation results change.')
    + source('routes/console.php', 'bootstrap/app.php', 'config/queue.php'), 'Development')

page('6. PROPOSED ENHANCEMENTS', p('The following items are future work. They are not presented as existing features of the submitted application.')
    + table(['Priority', 'Enhancement', 'Expected benefit / dependency'], [
        ('Immediate', 'Resolve current test failures', 'Enable CLI GD and reconcile the expected/implemented payment-attempt state.'),
        ('Immediate', 'Complete CSV product import', 'Implement validation, row-level feedback and safe catalogue updates for the existing route.'),
        ('High', 'Payment reconciliation tools', 'Help administrators compare pending UPI orders, receipt references and recorded refunds.'),
        ('High', 'Image optimization and delivery', 'Generate appropriately sized images and evaluate caching/CDN support.'),
        ('Medium', 'Drag-and-drop category ordering', 'Improve administrative catalogue organization.'),
        ('Medium', 'Transactional SMS / WhatsApp', 'Add optional order updates through a configured provider and consent workflow.'),
        ('Medium', 'Advanced search', 'Evaluate a dedicated search engine when catalogue size requires it.'),
        ('Later', 'Additional languages / currencies', 'Extend content and pricing only after suitable business and integration rules are designed.'),
        ('Later', 'Mobile application / PWA', 'Provide an installable experience with defined account, checkout and notification behavior.'),
        ('Later', 'Product recommendations', 'Evaluate consent, data quality and measurable usefulness before selecting rules or AI methods.'),
    ])
    + p('Prioritize correctness, reproducible tests, live integration verification and recoverability before expanding the product scope.'), 'Proposed Enhancements')

page('7. CONCLUSION', p('ARSH PERFUME brings fragrance discovery, shopping, order processing and store administration into one web application. The implementation includes a responsive storefront, product variants, carts, coupons, customer accounts, multiple payment paths, inventory tracking, fulfilment tools and owner-managed content.')
    + p('The principal engineering contribution is the connection between order integrity and day-to-day retail operations: totals are calculated on the server, stock is reserved transactionally, purchase details are snapshotted, ownership is checked and payment confirmation is kept separate from customer-entered claims. The administration panel adds the operational tools required to maintain this information.')
    + sub('Technical learning and challenges represented by the project')
    + ul('Separating request handling, reusable business rules and persistence.', 'Handling simultaneous stock changes and repeated order/payment events.', 'Protecting customer data while supporting both guests and registered buyers.', 'Integrating external identity/payment services without trusting browser-only outcomes.', 'Building responsive customer and administration views with a shared backend.', 'Using tests and documentation to expose unresolved environment or behavior gaps.')
    + sub('Current submission status')
    + p('The source, diagrams, schema dictionary, screenshots and setup/operation instructions are documented in the format of the supplied UDP reference. The known two test failures and incomplete CSV import remain explicit. Real configured provider flows and deployment operation require separate verification before a production-readiness claim can be made.')
    + p('The project provides a maintainable foundation for an online fragrance store. Future work should first strengthen verification and reconciliation, then add capabilities that are justified by customer and store-owner needs.'), 'Conclusion')

references = [
    ('Laravel', 'Laravel 12.x Documentation', 'https://laravel.com/docs/12.x', 'Framework architecture, routing, validation, ORM, queues and application setup.'),
    ('Laravel', 'Laravel Socialite — Laravel 12.x', 'https://laravel.com/docs/12.x/socialite', 'Google OAuth integration framework.'),
    ('PHP Documentation Group', 'PHP Manual', 'https://www.php.net/manual/en/', 'PHP language, runtime and extension reference.'),
    ('Oracle / MySQL', 'MySQL 8.0 Reference Manual', 'https://dev.mysql.com/doc/refman/8.0/en/', 'Relational database and InnoDB reference.'),
    ('Tailwind Labs', 'Installing Tailwind CSS with Vite', 'https://tailwindcss.com/docs/installation/using-vite', 'Tailwind and Vite asset integration.'),
    ('Vite', 'Getting Started', 'https://vite.dev/guide/', 'Frontend build and development workflow.'),
    ('Razorpay', 'PHP Payment Integration Steps', 'https://razorpay.com/docs/payments/server-integration/php/integration-steps/', 'Checkout/payment integration and verification guidance.'),
]
page('8. BIBLIOGRAPHY', ''.join(p(f'[{i}] {esc(author)}, <em>{esc(title)}</em>. <a href="{url}">{url}</a>. Accessed 27-10-2026. {esc(purpose)}') for i,(author,title,url,purpose) in enumerate(references,1))
    + sub('Project primary sources')
    + p('[8] ARSH PERFUME source repository: composer.json, package.json, routes/, app/, database/migrations/, resources/ and tests/. Inspected 27-10-2026. These define the application-specific behavior described in this report.')
    + p('[9] Existing project references: docs/ARCHITECTURE.md, docs/DEVELOPER_GUIDE.md, docs/DATABASE_REFERENCE.md, docs/CONFIG_REFERENCE.md and docs/google-login-setup.md. Current source takes precedence over outdated historical notes.')
    + p('[10] Supplied academic format: udpdocumentation.docx, provided in the local Downloads directory. Used for the chapter order, subsection structure and submission-page format; the unrelated sample-project content was replaced.')
    + note('No personal credentials, private database rows or real bank details are included. Saved interface captures are identified as previews. The report date is the supplied submission date, 28-10-2026.'), 'Bibliography', 'bibliography')

# Linked contents mirror the chapter/subsection order of the reference.
toc_entries = []
seen = set()
for index, item in enumerate(PAGES,1):
    if index <= 3 or 'continued' in item['title'].lower(): continue
    title = item['title']
    if title not in seen:
        toc_entries.append((title,index))
        seen.add(title)
    for heading in re.findall(r'<h2>(.*?)</h2>',item['body']):
        if re.match(r'\d\.\d ',heading) and heading not in seen:
            toc_entries.append((heading,index))
            seen.add(heading)
split = 21
for slot, entries in [(1,toc_entries[:split]),(2,toc_entries[split:])]:
    PAGES[slot]['body'] = '<nav>' + ''.join(f'<a class="{ "main" if re.match(r"^[1-8]\. ",label) else "child" }" href="#page-{number}"><span>{esc(label)}</span><b>{number}</b></a>' for label,number in entries) + '</nav>'
    if slot == 2:
        PAGES[slot]['body'] += note('The eight chapters follow the supplied UDP documentation structure. Sections 5.6–5.8 add setup, validation and operating information. The data dictionary covers all 36 migration-declared tables.')

CSS = '''
@page { size: Letter; margin: 12.7mm 25.4mm; }
* { box-sizing: border-box; }
html { background: #e8e8e8; }
body { margin:0; color:#171717; font:11pt/1.42 Calibri,Arial,sans-serif; }
.sheet { position:relative; width:165.1mm; height:253mm; margin:10mm auto; background:white; break-after:page; }
.sheet:last-child { break-after:auto; }
.page-head { height:9mm; border-bottom:1px solid #9eacc0; display:flex; justify-content:space-between; align-items:flex-start; font-size:8pt; color:#536b84; }
.body { padding-top:5mm; padding-bottom:9mm; }
h1 { font: bold 16pt/1.2 Calibri,Arial,sans-serif; color:#365f91; margin:0 0 5mm; }
h2 { font:bold 13pt/1.2 Calibri,Arial,sans-serif; color:#4f81bd; margin:4mm 0 2.5mm; }
h3 { font:bold 11.5pt/1.25 Calibri,Arial,sans-serif; color:#263b50; margin:3.5mm 0 2mm; }
p { margin:0 0 3.2mm; text-align:justify; }
ul,ol { margin:0 0 3.5mm; padding-left:6mm; }
li { padding-left:.5mm; margin:0 0 1.9mm; }
table { width:100%; border-collapse:collapse; table-layout:fixed; margin:3mm 0 4mm; font-size:9.8pt; line-height:1.3; }
th,td { border:1px solid #8c99a7; padding:2mm; vertical-align:top; overflow-wrap:anywhere; }
th { background:#e4edf7; color:#203b60; font-weight:bold; text-align:left; }
th:first-child { width:29%; }
tr { break-inside:avoid; }
pre { white-space:pre-wrap; overflow-wrap:anywhere; font:8.7pt/1.45 Consolas,'Courier New',monospace; background:#f2f4f6; border:1px solid #ccd3dc; padding:3mm; margin:3mm 0 4mm; }
code { font:.88em Consolas,monospace; overflow-wrap:anywhere; }
a { color:#365f91; overflow-wrap:anywhere; }
.note { margin:3mm 0; padding:2.5mm 3mm; background:#f1f5fa; border-left:3px solid #779bc3; font-size:9.2pt; line-height:1.4; }
.source { margin:3mm 0 0; font-size:8pt; color:#596a7b; text-align:left; overflow-wrap:anywhere; }
.page-foot { position:absolute; bottom:0; width:100%; height:7mm; border-top:1px solid #9eacc0; display:flex; justify-content:space-between; align-items:flex-end; color:#536b84; font-size:8pt; }
.cover .page-head,.cover .page-foot { display:none; }
.cover .body { padding-top:7mm; }
.university { text-align:center; font-size:25pt; font-weight:bold; color:#17365d; margin-bottom:3mm; }
.department { text-align:center; font-size:15pt; color:#365f91; }
.academic-rule { border-bottom:2px solid #4f81bd; margin:6mm 0 10mm; }
.project-name { text-align:center; font-size:29pt; margin-bottom:3mm; }
.project-subtitle { text-align:center; font-size:19pt; color:#365f91; margin-bottom:5mm; }
.report-label { text-align:center; font-size:12pt; font-weight:bold; margin-bottom:5mm; }
.programme { text-align:center; font-size:11pt; font-weight:bold; margin-bottom:8mm; }
.cover-table { font-size:11pt; line-height:1.7; }
.cover-table th { display:none; }
.cover-table td { padding:3mm; }
.cover-table td:first-child { width:33%; font-weight:bold; color:#17365d; }
.guide { text-align:center; margin-top:8mm; font-size:12pt; line-height:1.8; }
.toc nav { margin-top:4mm; }
.toc a { display:flex; justify-content:space-between; gap:5mm; padding:2.1mm 0; border-bottom:1px dotted #b4bdc9; text-decoration:none; font-size:10.5pt; line-height:1.35; }
.toc a.main { font-weight:bold; color:#17365d; margin-top:1.4mm; }
.toc a.child { padding-left:6mm; }
.toc a b { flex-shrink:0; }
.gantt { font-size:8pt; text-align:center; }
.gantt th,.gantt td { padding:2mm .6mm; }
.gantt th:first-child { width:33%; }
.gantt td:first-child { text-align:left; }
.bar { color:#365f91; font-size:13pt; }
.team th:first-child { width:29%; }.team th:nth-child(2) { width:18%; }
.requirements th:first-child { width:10%; }.requirements th:nth-child(2) { width:31%; }
.requirements { font-size:9.4pt; }
.diagram { margin:3mm 0 4mm; }.diagram img { display:block; width:100%; max-height:174mm; object-fit:contain; }
figcaption { color:#455b74; text-align:center; font-size:9pt; margin:2mm 0 0; line-height:1.3; }
.activity .diagram img { max-height:185mm; }
.screenshot { margin:3mm 0 4mm; }.screenshot img { display:block; max-width:100%; width:100%; max-height:139mm; object-fit:contain; border:1px solid #aab4c0; }
.screens p,.screens li { font-size:10.2pt; }
.screens h2 { margin-top:2mm; }
.dictionary h1 { font-size:14pt; margin-bottom:3mm; }
.dictionary h2 { font: bold 11pt Calibri,Arial,sans-serif; color:#365f91; margin:3mm 0 1.5mm; }
.schema { font:8.2pt/1.32 Consolas,monospace; margin:2mm 0 3mm; }
.schema th,.schema td { padding:1.4mm 1.8mm; }
.schema th:first-child { width:28%; }
.libraries { font-size:9pt; }.libraries th:first-child { width:26%; }.libraries th:nth-child(2) { width:24%; }
.bibliography p { font-size:10.2pt; margin-bottom:4mm; }
@media print { html { background:white; } .sheet { margin:0; } * { print-color-adjust:exact; -webkit-print-color-adjust:exact; } }
'''

HTML_PATH = DOCS / 'ARSH_PERFUME_UDP_DOCUMENTATION.html'
PDF_PATH = DOCS / 'ARSH_PERFUME_UDP_DOCUMENTATION.pdf'
sections = []
for index,item in enumerate(PAGES,1):
    title = '' if item['cls']=='cover' else '<h1>'+esc(item['title'])+'</h1>'
    sections.append(f'<section class="sheet {item["cls"]}" id="page-{index}"><header class="page-head"><span>ARSH PERFUME — PROJECT DOCUMENTATION</span><span>{esc(item["chapter"])}</span></header><main class="body">{title}{item["body"]}</main><footer class="page-foot"><span>LJ University · Academic Year 2026-2027</span><span>{index}</span></footer></section>')
document = '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>ARSH PERFUME — UDP Project Documentation — 2026-2027</title><style>'+CSS+'</style></head><body>'+''.join(sections)+'</body></html>'
HTML_PATH.write_text(document,encoding='utf-8')

if __name__=='__main__':
    from playwright.sync_api import sync_playwright
    from pypdfium2 import PdfDocument
    import pdfplumber
    with sync_playwright() as runtime:
        browser = runtime.chromium.launch(headless=True)
        tab = browser.new_page(viewport={'width':1000,'height':1200})
        tab.goto(HTML_PATH.as_uri(),wait_until='load')
        tab.emulate_media(media='print')
        tab.evaluate('document.fonts.ready')
        overflow = tab.evaluate('''() => [...document.querySelectorAll('.sheet')].map((s,i)=>({page:i+1,title:s.querySelector('h1')?.textContent,overlap:Math.ceil(s.querySelector('.body').getBoundingClientRect().bottom-s.querySelector('.page-foot').getBoundingClientRect().top)})).filter(x=>x.page!==1 && x.overlap>0)''')
        if overflow:
            browser.close()
            raise RuntimeError('Layout overflow: '+json.dumps(overflow))
        assert tab.evaluate('''() => [...document.querySelectorAll('a[href^="#"]')].every(a=>document.getElementById(a.hash.slice(1)))''')
        tab.pdf(path=str(PDF_PATH),format='Letter',print_background=True,prefer_css_page_size=True,tagged=True,outline=True)
        browser.close()
    pdf = PdfDocument(PDF_PATH)
    assert len(pdf)==len(PAGES),f'Unexpected printed page count: {len(pdf)} / {len(PAGES)}'
    assert len(definitions)==36
    previews = ROOT / 'storage/framework/academic-documentation-preview'
    previews.mkdir(exist_ok=True)
    wanted = [1,2,4,10]
    wanted += [i for i,x in enumerate(PAGES,1) if x['title'] in ['4.2 Class Diagram','4.4 Activity Diagram','5.5 Screenshots & Implementation Details','8. BIBLIOGRAPHY']]
    for number in wanted:
        pdf[number-1].render(scale=1.3).to_pil().save(previews/f'page-{number:02}.png')
    with pdfplumber.open(PDF_PATH) as parsed:
        first = parsed.pages[0].extract_text()
        required = [name for name,roll in MEMBERS]+[roll for name,roll in MEMBERS]+['Nirav Rajgor sir','2026-2027','28-10-2026']
        assert all(x in first for x in required),'Missing submission detail'
    print(json.dumps({'pdf':str(PDF_PATH),'html':str(HTML_PATH),'pages':len(pdf),'schema_tables':len(definitions),'submission_details_verified':True,'bytes':PDF_PATH.stat().st_size},indent=2))
