<!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', setting('business_name', 'Arsh Perfume').' | '.setting('tagline'))</title>
    <meta name="description" content="@yield('meta_description', 'Premium attars, roll-on perfumes and fragrance oils from Arsh Perfume. Feel the best quality.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:site_name" content="{{ setting('business_name', 'Arsh Perfume') }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', 'Arsh Perfume')">
    <meta property="og:description" content="@yield('meta_description', 'Premium attars and perfumes.')">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif
    <link rel="icon" href="{{ asset('images/brand/arsh-logo.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="storefront flex min-h-screen flex-col">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-gold focus:px-4 focus:py-2 focus:text-ink">Skip to content</a>

<div class="announcement">A world of fragrance. A scent that's yours. <a href="{{ route('shop') }}">Discover Arsh &rarr;</a></div>
<header class="store-header sticky top-0 z-40 border-b border-line" x-data="{ open: false }" @keydown.escape.window="open = false">
    <div class="container-x flex h-20 items-center justify-between gap-6">
        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="{{ setting('business_name') }} home">
            <img src="{{ asset('images/brand/arsh-logo.jpg') }}" alt="" class="logo-blend h-10 w-auto" width="60" height="40">
            <span class="brand-wordmark">ARSH<span>THE FRAGRANCE HOUSE</span></span>
        </a>

        <nav class="hidden items-center gap-7 text-sm uppercase tracking-widest text-muted lg:flex" aria-label="Main">
            <a href="{{ route('shop') }}" class="hover:text-gold {{ request()->routeIs('shop') ? 'text-gold' : '' }}">All Fragrances</a>
            <a href="{{ route('collections.index') }}" class="hover:text-gold">Collections</a>
            <a href="{{ route('track-order') }}" class="hover:text-gold">Track Order</a>
            <a href="{{ route('pages.about') }}" class="hover:text-gold">About</a>
            <a href="{{ route('contact') }}" class="hover:text-gold">Contact</a>
        </nav>

        <div class="flex items-center gap-4 text-sm">
            <form action="{{ route('shop') }}" method="get" class="hidden md:block" role="search">
                <label for="site-search" class="sr-only">Search products</label>
                <input id="site-search" name="q" type="search" value="{{ request('q') }}" placeholder="Search" class="input w-44 py-2">
            </form>
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="hidden font-medium text-gold hover:text-gold-soft sm:inline">Dashboard</a>
                @endif
                <a href="{{ route('account') }}" class="hidden hover:text-gold sm:inline">Account</a>
                <a href="{{ route('wishlist.index') }}" class="hidden hover:text-gold sm:inline">Wishlist</a>
            @else
                <a href="{{ route('login') }}" class="hidden hover:text-gold sm:inline">Sign in</a>
            @endauth
            <a href="{{ route('cart.index') }}" class="relative hover:text-gold" aria-label="Cart, {{ $cartCount ?? 0 }} items">
                Cart
                @if(($cartCount ?? 0) > 0)
                    <span class="ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-gold px-1 text-[11px] font-semibold text-ink">{{ $cartCount }}</span>
                @endif
            </a>
            <button type="button" class="menu-toggle lg:hidden" @click="open = !open" :aria-expanded="open" aria-controls="mobile-nav" aria-label="Toggle menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>
        </div>
    </div>

    <nav id="mobile-nav" x-cloak x-show="open" class="border-t border-line bg-panel lg:hidden" aria-label="Mobile">
        <div class="container-x flex flex-col gap-1 py-4 text-sm uppercase tracking-widest">
            <form action="{{ route('shop') }}" method="get" role="search" class="mb-3"><label for="mobile-search" class="sr-only">Search fragrances</label><input id="mobile-search" type="search" name="q" class="input" placeholder="Find your fragrance" value="{{ request('q') }}"></form>
            <a class="py-2" href="{{ route('shop') }}">Shop</a>
            <a class="py-2" href="{{ route('collections.index') }}">Collections</a>
            <a class="py-2" href="{{ route('track-order') }}">Track Order</a>
            <a class="py-2" href="{{ route('pages.about') }}">About</a>
            <a class="py-2" href="{{ route('contact') }}">Contact</a>
            @auth
                <a class="py-2" href="{{ route('account') }}">My Account</a>
                <a class="py-2" href="{{ route('wishlist.index') }}">Wishlist</a>
                @if(auth()->user()->isAdmin())<a class="py-2 text-gold" href="{{ route('admin.dashboard') }}">Admin</a>@endif
                <form method="post" action="{{ route('logout') }}">@csrf<button class="py-2 text-left">Sign out</button></form>
            @else
                <a class="py-2" href="{{ route('login') }}">Sign in</a>
                <a class="py-2" href="{{ route('register') }}">Create account</a>
            @endauth
        </div>
    </nav>
</header>

<main id="main" class="flex-1">
    <div class="container-x {{ session('status') || session('error') || $errors->any() ? 'pt-6' : '' }}">
        @if(session('status'))<div class="alert-ok mb-4" role="status">{{ session('status') }}</div>@endif
        @if(session('error'))<div class="alert-error mb-4" role="alert">{{ session('error') }}</div>@endif
        @if($errors->any())
            <div class="alert-error mb-4" role="alert">Please check the highlighted fields and try again.</div>
        @endif
    </div>
    @yield('content')
</main>

<footer class="store-footer mt-24 border-t border-line bg-panel">
    <div class="container-x grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="font-display text-xl tracking-[0.2em]">ARSH PERFUME</p>
            <p class="mt-3 text-sm text-muted">{{ setting('tagline') }}</p>
            @if(setting('social_instagram') || setting('social_facebook') || setting('social_youtube'))
                <ul class="mt-5 flex gap-4 text-sm text-muted">
                    @if(setting('social_instagram'))<li><a class="hover:text-gold" href="{{ setting('social_instagram') }}" rel="noopener" target="_blank">Instagram</a></li>@endif
                    @if(setting('social_facebook'))<li><a class="hover:text-gold" href="{{ setting('social_facebook') }}" rel="noopener" target="_blank">Facebook</a></li>@endif
                    @if(setting('social_youtube'))<li><a class="hover:text-gold" href="{{ setting('social_youtube') }}" rel="noopener" target="_blank">YouTube</a></li>@endif
                </ul>
            @endif
        </div>
        <div class="text-sm">
            <p class="mb-4 font-medium uppercase tracking-widest text-white">Help</p>
            <ul class="space-y-2 text-muted">
                <li><a class="hover:text-gold" href="{{ route('pages.faq') }}">FAQ</a></li>
                <li><a class="hover:text-gold" href="{{ route('pages.shipping') }}">Shipping Policy</a></li>
                <li><a class="hover:text-gold" href="{{ route('pages.returns') }}">Returns &amp; Refunds</a></li>
                <li><a class="hover:text-gold" href="{{ route('pages.cancellation') }}">Cancellation Policy</a></li>
                <li><a class="hover:text-gold" href="{{ route('pages.payment') }}">Payment Information</a></li>
                <li><a class="hover:text-gold" href="{{ route('track-order') }}">Track Your Order</a></li>
            </ul>
        </div>
        <div class="text-sm">
            <p class="mb-4 font-medium uppercase tracking-widest text-white">Legal</p>
            <ul class="space-y-2 text-muted">
                <li><a class="hover:text-gold" href="{{ route('pages.privacy') }}">Privacy Policy</a></li>
                <li><a class="hover:text-gold" href="{{ route('pages.terms') }}">Terms &amp; Conditions</a></li>
                <li><a class="hover:text-gold" href="{{ route('pages.about') }}">About Us</a></li>
                @foreach(collect(preg_split('/\R/', (string) setting('footer_links')))->filter() as $line)
                    @php([$label, $url] = array_pad(explode('|', $line, 2), 2, '#'))
                    <li><a class="hover:text-gold" href="{{ trim($url) }}">{{ trim($label) }}</a></li>
                @endforeach
            </ul>
        </div>
        <div class="text-sm text-muted">
            <p class="mb-4 font-medium uppercase tracking-widest text-white">Contact</p>
            @if(setting('business_address'))<p class="mb-2 whitespace-pre-line">{{ setting('business_address') }}</p>@endif
            @if(setting('contact_email'))<p class="mb-2"><a class="hover:text-gold" href="mailto:{{ setting('contact_email') }}">{{ setting('contact_email') }}</a></p>@endif
            @if(setting('contact_phone'))<p class="mb-2"><a class="hover:text-gold" href="tel:{{ setting('contact_phone') }}">{{ setting('contact_phone') }}</a></p>@endif
            @if(setting('business_hours'))<p>{{ setting('business_hours') }}</p>@endif
            @if(setting('gstin'))<p class="mt-3 text-xs">GSTIN: {{ setting('gstin') }}</p>@endif
        </div>
    </div>
    <div class="border-t border-line">
        <div class="container-x flex flex-col gap-2 py-6 text-xs text-muted sm:flex-row sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ setting('business_name', 'Arsh Perfume') }}. All rights reserved.</p>
            <p>{{ setting('footer_text') }}</p>
        </div>
    </div>
</footer>

{{-- Floating WhatsApp contact / order CTA --}}
@php($waNumber = preg_replace('/\D/', '', (string) config('arsh.whatsapp_number', '919724745018')))
<a href="https://wa.me/{{ $waNumber }}?text={{ rawurlencode('Hi Arsh Perfume, I would like to place an order.') }}"
   target="_blank" rel="noopener" aria-label="Order on WhatsApp"
   class="fixed bottom-5 right-5 z-50 flex items-center gap-2 rounded-full bg-[#25D366] px-4 py-3 text-sm font-semibold text-[#06281a] shadow-lg transition hover:brightness-105">
    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.945C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.82 9.82 0 001.516 5.256l-.999 3.648 3.472-.903zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
    <span class="hidden sm:inline">Order on WhatsApp</span>
</a>
</body>
</html>
