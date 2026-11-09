<!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') | Arsh Perfume Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js'])
    @stack('head')
</head>
<body class="storefront admin-app min-h-screen" x-data="{ nav: false, desktop: window.innerWidth >= 1024, toggleNav() { this.nav = !this.nav; this.$nextTick(() => this.nav ? this.$refs.sideClose.focus() : this.$refs.menuToggle.focus()); } }" @resize.window="desktop = window.innerWidth >= 1024; if(desktop) nav = false" @keydown.escape.window="if(nav) toggleNav()" :class="{ 'admin-drawer-open': nav && !desktop }">
<a href="#admin-main" class="admin-skip">Skip to main content</a>
<div class="admin-shell">
    <div x-cloak x-show="nav && !desktop" class="admin-backdrop" @click="toggleNav()" aria-hidden="true"></div>
    <aside id="admin-sidebar" x-cloak x-show="nav || desktop" class="admin-sidebar" :role="desktop ? 'complementary' : 'dialog'" :aria-modal="!desktop && nav ? 'true' : null" aria-label="Admin navigation" @keydown.tab="if(!desktop) { const elements = Array.from($el.querySelectorAll('a[href],button:not([disabled])')).filter(el => el.getClientRects().length); const first = elements[0]; const last = elements[elements.length - 1]; if($event.shiftKey && document.activeElement === first) { $event.preventDefault(); last.focus(); } else if(!$event.shiftKey && document.activeElement === last) { $event.preventDefault(); first.focus(); } }">
        <div class="admin-brand-row"><a href="{{ route('admin.dashboard') }}" class="brand-wordmark" aria-label="Arsh admin overview">ARSH<span>THE FRAGRANCE HOUSE</span></a><button type="button" class="admin-icon-button lg:hidden" x-ref="sideClose" @click="toggleNav()" aria-label="Close navigation"><x-admin-icon name="close" /></button></div>
        <div class="admin-workspace"><span class="workspace-monogram">A</span><div><strong>Arsh workspace</strong><span>Store administration</span></div><span class="workspace-dot" aria-label="Admin workspace"></span></div>
        @php($groups = [
            'WORKSPACE' => [
                ['admin.dashboard', 'Overview', 'admin.dashboard', 'grid'],
                ['admin.orders.index', 'Orders', 'admin.orders.*', 'bag'],
                ['admin.products.index', 'Products', 'admin.products.*', 'bottle'],
                ['admin.categories.index', 'Collections', 'admin.categories.*', 'layers'],
                ['admin.inventory.index', 'Inventory', 'admin.inventory.*', 'box'],
                ['admin.customers.index', 'Customers', 'admin.customers.*', 'users'],
            ],
            'GROWTH & INSIGHTS' => [
                ['admin.reports.index', 'Reports', 'admin.reports.*', 'chart'],
                ['admin.coupons.index', 'Coupons', 'admin.coupons.*', 'tag'],
                ['admin.reviews.index', 'Reviews', 'admin.reviews.*', 'star'],
                ['admin.enquiries.index', 'Enquiries', 'admin.enquiries.*', 'message'],
                ['admin.newsletter.index', 'Newsletter', 'admin.newsletter.*', 'mail'],
            ],
            'STORE MANAGEMENT' => [
                ['admin.banners.index', 'Banners', 'admin.banners.*', 'image'],
                ['admin.pages.index', 'Pages & policies', 'admin.pages.*', 'file'],
                ['admin.faqs.index', 'FAQs', 'admin.faqs.*', 'help'],
                ['admin.settings.edit', 'Settings', 'admin.settings.*', 'settings'],
                ['admin.activity-log.index', 'Activity log', 'admin.activity-log.*', 'activity'],
            ],
        ])
        <nav class="admin-nav" aria-label="Administration">
            @foreach($groups as $group => $links)
                <p class="admin-nav-label">{{ $group }}</p><ul>
                @foreach($links as [$route, $label, $pattern, $icon])
                    <li><a href="{{ route($route) }}" class="admin-nav-link {{ request()->routeIs($pattern) ? 'is-active' : '' }}" @if(request()->routeIs($pattern)) aria-current="page" @endif><x-admin-icon :name="$icon" /><span>{{ $label }}</span>@if(request()->routeIs($pattern))<span class="nav-active-dot" aria-hidden="true"></span>@endif</a></li>
                @endforeach
                </ul>
            @endforeach
        </nav>
        <div class="admin-sidebar-foot"><a href="{{ route('home') }}" class="admin-store-link"><x-admin-icon name="arrow" /> View storefront</a><div class="admin-profile"><span class="admin-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><span>Administrator</span></div><form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="admin-icon-button" aria-label="Sign out"><x-admin-icon name="logout" /></button></form></div></div>
    </aside>
    <div class="admin-content" :inert="nav && !desktop">
        <header class="admin-topbar"><div class="admin-breadcrumb"><button type="button" x-ref="menuToggle" class="admin-icon-button lg:hidden" @click="toggleNav()" :aria-expanded="nav" aria-controls="admin-sidebar" aria-label="Open navigation"><x-admin-icon name="menu" /></button><span class="admin-breadcrumb-house">Workspace</span><span class="admin-breadcrumb-separator" aria-hidden="true">/</span><h1>@yield('heading', 'Dashboard')</h1></div><div class="admin-topbar-tools"><span class="admin-today">{{ now()->format('d M Y') }}</span><a href="{{ route('home') }}" class="admin-preview-link">View store <x-admin-icon name="arrow" /></a><span class="admin-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span></div></header>
        <main id="admin-main" class="admin-main" tabindex="-1">
            @if(session('status'))<div class="alert-ok" role="status">{{ session('status') }}</div>@endif
            @if(session('error'))<div class="alert-error" role="alert">{{ session('error') }}</div>@endif
            @if(session('missing') && count(session('missing')))
                <div class="alert-info" role="status"><p class="font-medium">Still needed before this product can be published:</p><ul class="mt-1 list-disc pl-5">@foreach(session('missing') as $m)<li>{{ $m }}</li>@endforeach</ul></div>
            @endif
            @if($errors->any())
                <div class="alert-error" role="alert"><p class="font-medium">Please fix the following:</p><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
            @yield('content')
        </main>
        <footer class="admin-footer"><span>&copy; {{ date('Y') }} Arsh Perfume</span><span>Thoughtfully crafted. Beautifully managed.</span></footer>
    </div>
</div>
</body>
</html>
