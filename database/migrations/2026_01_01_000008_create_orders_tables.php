<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete(); // null = guest checkout
            $table->string('idempotency_key', 64)->nullable()->unique(); // blocks duplicate submissions

            $table->string('status', 24)->default('pending');
            $table->string('payment_method', 24);                        // cod | razorpay
            $table->string('payment_status', 24)->default('pending');    // pending | paid | failed | refunded

            // Contact + delivery address are snapshots (later address-book edits must not change past orders).
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20);
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('postal_code', 10);
            $table->string('country', 60)->default('India');
            $table->text('notes')->nullable();

            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->decimal('refunded_amount', 12, 2)->default(0);

            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code', 50)->nullable();               // snapshot

            $table->string('courier_name', 100)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->string('tracking_url')->nullable();

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamp('stock_restored_at')->nullable();          // makes stock restoration idempotent
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('payment_status');
            $table->index('payment_method');
            $table->index('email');
            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();

            // Historical snapshot: invoices stay correct even if the product is edited or archived later.
            $table->string('product_name');
            $table->string('variant_name', 80)->nullable();
            $table->string('sku', 64);
            $table->text('description_snapshot')->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('list_price', 10, 2);   // regular price at purchase time
            $table->decimal('unit_price', 10, 2);   // price actually charged (sale price if on sale)
            $table->unsignedSmallInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('provider', 24);                         // cod | razorpay
            $table->string('provider_order_id', 80)->nullable()->index();
            $table->string('provider_payment_id', 80)->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('status', 24)->default('created');       // created | paid | failed | refunded
            $table->string('method', 40)->nullable();               // upi, card ... as reported by the gateway
            $table->string('error_code', 80)->nullable();
            $table->string('error_description')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->json('meta')->nullable();                       // gateway ids only. NEVER card data.
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        // One row per webhook delivery. The unique key makes webhook handling idempotent.
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 24);
            $table->string('event_id', 100);
            $table->string('event_type', 80);
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('outcome')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->restrictOnDelete(); // used coupons are deactivated, not deleted
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();

            $table->unique(['coupon_id', 'order_id']);
            $table->index(['coupon_id', 'user_id']);
            $table->index(['coupon_id', 'email']);
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->string('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
