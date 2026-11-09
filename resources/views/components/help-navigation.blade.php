<aside class="help-sidebar">
    <nav aria-label="Customer care">
        <p class="eyebrow">CUSTOMER CARE</p>
        @foreach([
            'pages.faq' => 'FAQ', 'pages.shipping' => 'Shipping Policy',
            'pages.returns' => 'Returns & Refunds', 'pages.cancellation' => 'Cancellation Policy',
            'pages.payment' => 'Payment Information', 'track-order' => 'Track Your Order',
            'pages.privacy' => 'Privacy Policy', 'pages.terms' => 'Terms & Conditions',
            'pages.about' => 'About Us', 'shop' => 'Shop All', 'contact' => 'Contact Us',
        ] as $route => $label)
            <a href="{{ route($route) }}" @if(request()->routeIs($route)) aria-current="page" @endif>{{ $label }}<span aria-hidden="true">&rarr;</span></a>
        @endforeach
    </nav>
    <div class="help-note"><p class="font-display">A little guidance?</p><p>From choosing a scent to questions about your order, we’re here to help.</p><a href="{{ route('contact') }}" class="text-link">Get in touch &rarr;</a></div>
</aside>
