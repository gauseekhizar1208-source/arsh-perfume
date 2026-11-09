<?php

/*
 * Arsh Perfume business configuration.
 * Anything an owner should change at runtime lives in the `settings` table (see 'settings' defaults below);
 * this file holds structural constants and the defaults those settings fall back to.
 */
return [
    'brand' => 'ARSH PERFUME',
    'tagline' => 'Feel the Best Quality.',

    // Disk for product/banner/brand images. 'public' in development, 's3' (or similar) in production.
    'media_disk' => env('MEDIA_DISK', 'public'),

    // Order lifecycle -------------------------------------------------------
    'order_statuses' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned',
    ],

    // Allowed forward moves. Anything not listed here is rejected by OrderService::transition().
    'status_transitions' => [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['out_for_delivery', 'delivered', 'returned'],
        'out_for_delivery' => ['delivered', 'returned'],
        'delivered' => ['returned'],
        'cancelled' => [],
        'returned' => [],
    ],

    // A customer may cancel their own order only while it is in one of these statuses.
    'customer_cancellable_statuses' => ['pending', 'confirmed'],

    'payment_statuses' => [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ],

    // Orders are placed over WhatsApp — no online payment gateways.
    // 'whatsapp' is the only method offered at checkout. The legacy keys are kept
    // only so any historical orders still render a readable payment label.
    'payment_methods' => [
        'whatsapp' => 'WhatsApp Order',
        'cod' => 'Cash on Delivery',
        'razorpay' => 'Online Payment (Razorpay)',
        'upi' => 'UPI QR (manual verification)',
    ],

    // WhatsApp number that receives orders (digits only, country code first).
    'whatsapp_number' => env('WHATSAPP_NUMBER', '919724745018'),

    // First administrator. Read by AdminUserSeeder / `arsh:create-admin` only. Remove from .env after first use.
    'admin' => [
        'name' => env('ADMIN_NAME'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    // Unpaid online orders release their reserved stock after this many minutes.
    'unpaid_order_expiry_minutes' => 30,

    'fragrance_families' => [
        'Floral', 'Woody', 'Musk', 'Oud', 'Fresh & Fruity', 'Spicy', 'Amber', 'Aquatic', 'Gourmand',
    ],

    'states' => [
        'Andaman and Nicobar Islands', 'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chandigarh',
        'Chhattisgarh', 'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Goa', 'Gujarat', 'Haryana',
        'Himachal Pradesh', 'Jammu and Kashmir', 'Jharkhand', 'Karnataka', 'Kerala', 'Ladakh', 'Lakshadweep',
        'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Puducherry',
        'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand',
        'West Bengal',
    ],

    // Defaults for the `settings` table. Admin > Settings overrides these. Format: key => [group, type, default]
    // Secret credentials are deliberately NOT here: they belong in .env.
    'settings' => [
        // business
        'business_name' => ['business', 'string', 'Arsh Perfume'],
        'tagline' => ['business', 'string', 'Feel the Best Quality.'],
        'contact_email' => ['business', 'string', ''],
        'contact_phone' => ['business', 'string', ''],
        'whatsapp_number' => ['business', 'string', ''],
        'business_address' => ['business', 'text', ''],
        'business_hours' => ['business', 'string', ''],
        'gstin' => ['business', 'string', ''],
        'logo_path' => ['business', 'string', ''],
        'favicon_path' => ['business', 'string', ''],
        'footer_text' => ['business', 'text', ''],
        'about_snippet' => ['business', 'text', ''],
        // social
        'social_instagram' => ['social', 'string', ''],
        'social_facebook' => ['social', 'string', ''],
        'social_youtube' => ['social', 'string', ''],
        'social_whatsapp' => ['social', 'string', ''],
        // commerce
        'currency_symbol' => ['commerce', 'string', '₹'],
        'tax_enabled' => ['commerce', 'bool', '0'],
        'tax_rate' => ['commerce', 'decimal', '0'],
        'prices_include_tax' => ['commerce', 'bool', '1'],
        'shipping_flat_rate' => ['commerce', 'decimal', '0'],
        'free_shipping_threshold' => ['commerce', 'decimal', '0'],
        'cod_enabled' => ['commerce', 'bool', '1'],
        'online_payment_enabled' => ['commerce', 'bool', '1'],
        'upi_enabled' => ['upi_payments', 'bool', '0'],
        'upi_payee_name' => ['upi_payments', 'string', ''],
        'upi_id' => ['upi_payments', 'string', ''],
        'guest_checkout_enabled' => ['commerce', 'bool', '1'],
        'default_low_stock_threshold' => ['commerce', 'int', '5'],
        // invoice
        'invoice_prefix' => ['invoice', 'string', 'INV-'],
        'invoice_footer' => ['invoice', 'text', 'Thank you for shopping with Arsh Perfume.'],
        'invoice_terms' => ['invoice', 'text', ''],
        // notifications
        'notify_admin_new_order' => ['notifications', 'bool', '1'],
        'notify_customer_status' => ['notifications', 'bool', '1'],
        'admin_notification_email' => ['notifications', 'string', ''],
        // homepage sections (CMS)
        'home_show_featured' => ['homepage', 'bool', '1'],
        'home_show_bestsellers' => ['homepage', 'bool', '1'],
        'home_show_new_arrivals' => ['homepage', 'bool', '1'],
        'home_show_categories' => ['homepage', 'bool', '1'],
        'home_show_why_choose' => ['homepage', 'bool', '1'],
        'home_show_reviews' => ['homepage', 'bool', '1'],
        'home_show_newsletter' => ['homepage', 'bool', '1'],
        'home_show_social' => ['homepage', 'bool', '1'],
        'hero_heading' => ['homepage', 'string', 'Feel the Best Quality.'],
        'hero_subheading' => ['homepage', 'text', 'Attars, roll-ons and fragrance oils crafted to be remembered.'],
        // footer links (one "Label|URL" per line)
        'footer_links' => ['business', 'text', ''],
    ],
];
