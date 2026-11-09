@extends('layouts.admin')

@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
@php($labels = [
    'business_name' => 'Business name', 'tagline' => 'Brand tagline', 'contact_email' => 'Contact email', 'contact_phone' => 'Contact phone',
    'whatsapp_number' => 'WhatsApp number', 'business_address' => 'Business address', 'business_hours' => 'Business hours', 'gstin' => 'GSTIN',
    'logo_path' => 'Logo path (storage)', 'favicon_path' => 'Favicon path (storage)', 'footer_text' => 'Footer text', 'about_snippet' => 'About snippet',
    'footer_links' => 'Footer links (one "Label|https://url" per line)',
    'social_instagram' => 'Instagram URL', 'social_facebook' => 'Facebook URL', 'social_youtube' => 'YouTube URL', 'social_whatsapp' => 'WhatsApp link',
    'currency_symbol' => 'Currency symbol', 'tax_enabled' => 'Show GST', 'tax_rate' => 'GST rate (%)', 'prices_include_tax' => 'Prices already include GST',
    'shipping_flat_rate' => 'Shipping charge (₹)', 'free_shipping_threshold' => 'Free shipping above (₹, 0 = never)', 'cod_enabled' => 'Accept Cash on Delivery',
    'online_payment_enabled' => 'Accept online payment', 'guest_checkout_enabled' => 'Allow guest checkout', 'default_low_stock_threshold' => 'Default low-stock alert',
    'invoice_prefix' => 'Invoice prefix', 'invoice_footer' => 'Invoice footer', 'invoice_terms' => 'Invoice terms',
    'notify_admin_new_order' => 'Email admin on new order', 'notify_customer_status' => 'Email customers on status changes', 'admin_notification_email' => 'Admin notification email',
    'home_show_featured' => 'Show featured', 'home_show_bestsellers' => 'Show bestsellers', 'home_show_new_arrivals' => 'Show new arrivals', 'home_show_categories' => 'Show categories',
    'home_show_why_choose' => 'Show "Why choose us"', 'home_show_reviews' => 'Show reviews', 'home_show_newsletter' => 'Show newsletter', 'home_show_social' => 'Show social links',
    'hero_heading' => 'Hero heading', 'hero_subheading' => 'Hero subheading',
    'upi_enabled' => 'Accept direct UPI QR payments (manual verification)',
    'upi_id' => 'Receiving UPI ID', 'upi_payee_name' => 'Account holder / payee name',
])
<form method="post" action="{{ route('admin.settings.update') }}" class="space-y-8">
    @csrf @method('PUT')
    <div class="card p-5 text-sm">
        <p class="font-medium">Payment gateway: <span class="text-gold">{{ strtoupper($razorpayMode) }}</span></p>
        <p class="mt-1 text-muted">Razorpay keys (and webhook secret) are set in the server's .env file only. They are never shown or stored here.</p>
        <p class="mt-4 font-medium">Google sign-in: <span class="text-gold">{{ app(\App\Services\GoogleSignInService::class)->isAvailable() ? 'Configured' : 'Not configured' }}</span></p>
        <p class="mt-1 text-muted">Google OAuth credentials are configured in the server's .env file. Callback URL: <code class="break-all">{{ config('services.google.redirect') }}</code></p>
    </div>

    @foreach($groups as $group => $fields)
        <section class="card space-y-5 p-6" aria-labelledby="g-{{ $group }}">
            <h2 id="g-{{ $group }}" class="font-display text-lg capitalize">{{ str_replace('_', ' ', $group) }}</h2>
            @if($group === 'upi_payments')<p class="text-sm text-muted">A QR is generated locally for each order with its exact amount. Verify the credit in your bank or UPI app before marking an order paid. Changes to the receiving account apply to new orders.</p>@endif
            <div class="grid gap-5 sm:grid-cols-2">
                @foreach($fields as $key => $value)
                    @php($type = $definitions[$key][1])
                    @if($type === 'bool')
                        <label class="flex items-center gap-3 self-end text-sm">
                            <input type="hidden" name="{{ $key }}" value="0">
                            <input type="checkbox" name="{{ $key }}" value="1" class="accent-[#C8A24D]" @checked($value)> {{ $labels[$key] ?? $key }}
                        </label>
                    @elseif($type === 'text')
                        <div class="sm:col-span-2"><label class="label" for="s-{{ $key }}">{{ $labels[$key] ?? $key }}</label><textarea id="s-{{ $key }}" name="{{ $key }}" rows="3" class="input">{{ old($key, $value) }}</textarea></div>
                    @else
                        <div><label class="label" for="s-{{ $key }}">{{ $labels[$key] ?? $key }}</label>
                            <input id="s-{{ $key }}" name="{{ $key }}" type="{{ $type === 'int' || $type === 'decimal' ? 'number' : 'text' }}" @if($type === 'decimal') step="0.01" @endif class="input" value="{{ old($key, $value) }}">
                            @error($key)<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endforeach

    <button class="btn-gold">Save settings</button>
</form>
@endsection
