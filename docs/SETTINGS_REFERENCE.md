# Runtime settings reference

[Documentation home](DOCUMENTATION.md)

Generated from `config/arsh.php` defaults on 2026-10-27. These are declared defaults, not values from the installed settings table. Administrators edit these under **Admin > Settings**. Google/Razorpay secrets, database credentials and mail credentials belong in environment configuration and are intentionally absent from this list.

Types describe how `Setting` casts values. Boolean defaults use `0`/`1`; `(empty)` means an empty string. Homepage switches control sections when their required content exists. Currency formatting is configurable, but checkout/payment currency is INR; changing the symbol does not implement currency conversion.

The settings form writes all boolean switches on submission; unchecked/omitted switches become false. Keep the complete form when extending the UI. Regenerate with `php docs/tools/generate-reference.php`.

| Key | Group | Type | Default |
|---|---|---|---|
| `business_name` | business | string | Arsh Perfume |
| `tagline` | business | string | Feel the Best Quality. |
| `contact_email` | business | string | (empty) |
| `contact_phone` | business | string | (empty) |
| `whatsapp_number` | business | string | (empty) |
| `business_address` | business | text | (empty) |
| `business_hours` | business | string | (empty) |
| `gstin` | business | string | (empty) |
| `logo_path` | business | string | (empty) |
| `favicon_path` | business | string | (empty) |
| `footer_text` | business | text | (empty) |
| `about_snippet` | business | text | (empty) |
| `social_instagram` | social | string | (empty) |
| `social_facebook` | social | string | (empty) |
| `social_youtube` | social | string | (empty) |
| `social_whatsapp` | social | string | (empty) |
| `currency_symbol` | commerce | string | ₹ |
| `tax_enabled` | commerce | bool | 0 |
| `tax_rate` | commerce | decimal | 0 |
| `prices_include_tax` | commerce | bool | 1 |
| `shipping_flat_rate` | commerce | decimal | 0 |
| `free_shipping_threshold` | commerce | decimal | 0 |
| `cod_enabled` | commerce | bool | 1 |
| `online_payment_enabled` | commerce | bool | 1 |
| `upi_enabled` | upi_payments | bool | 0 |
| `upi_payee_name` | upi_payments | string | (empty) |
| `upi_id` | upi_payments | string | (empty) |
| `guest_checkout_enabled` | commerce | bool | 1 |
| `default_low_stock_threshold` | commerce | int | 5 |
| `invoice_prefix` | invoice | string | INV- |
| `invoice_footer` | invoice | text | Thank you for shopping with Arsh Perfume. |
| `invoice_terms` | invoice | text | (empty) |
| `notify_admin_new_order` | notifications | bool | 1 |
| `notify_customer_status` | notifications | bool | 1 |
| `admin_notification_email` | notifications | string | (empty) |
| `home_show_featured` | homepage | bool | 1 |
| `home_show_bestsellers` | homepage | bool | 1 |
| `home_show_new_arrivals` | homepage | bool | 1 |
| `home_show_categories` | homepage | bool | 1 |
| `home_show_why_choose` | homepage | bool | 1 |
| `home_show_reviews` | homepage | bool | 1 |
| `home_show_newsletter` | homepage | bool | 1 |
| `home_show_social` | homepage | bool | 1 |
| `hero_heading` | homepage | string | Feel the Best Quality. |
| `hero_subheading` | homepage | text | Attars, roll-ons and fragrance oils crafted to be remembered. |
| `footer_links` | business | text | (empty) |
