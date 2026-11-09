# Administration

Extends the user-approved midnight, amber-glass and metallic-gold storefront direction. Both public and administrative layouts use the same `.storefront` tokens, Cormorant display type, Montserrat interface type, buttons, inputs, surface treatments and focus colours. `resources/css/admin.css` adds administration-only composition through `.admin-app`.

Layout: a 238px desktop navigation rail, 76px workspace header, four metric cards, a revenue panel with chart/date filters, inventory watch and order status panels, recent orders, and bestsellers. The navigation rail collapses below 1024px. Phone layouts use two metric columns and stack analytic panels.

Hierarchy: grouped navigation covers Workspace, Growth & Insights, and Store Management. The current route is indicated with `aria-current`. The mobile drawer restores focus on dismissal, traps keyboard focus, closes with Escape or backdrop click, and makes background content inert.

Data: all dashboard figures use the existing ReportService. No invented trend percentages or illustrative metrics. Lifetime statistics are labelled separately from the chart's selected date range. Stock alerts count active variants. Revenue matches the service definition: paid/refunded orders minus refunds, excluding cancelled/returned orders.

Charts: gold revenue line with restrained gradient fill; dashed sage revenue-order series on its own axis. Exact daily values and the revenue definition are available below the chart as an HTML table. Reduced motion disables Chart.js animation, including when the preference changes during use.

Materials: subtle dimensional metric icons and a static lit inventory sculpture echo the storefront without adding pointer-driven motion to operational controls. All existing CRUD forms, report links, filtering and authentication endpoints remain connected.

Validation: admin access tests, empty/populated dashboard rendering, revenue/date range distinction, inventory counts, sidebar state, chart initialization and phone/tablet/desktop overflow checks. Browser previews use generated SQLite test fixtures without creating a real administrator or changing live store data.
