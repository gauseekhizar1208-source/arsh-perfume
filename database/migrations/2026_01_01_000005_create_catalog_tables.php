<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // A category that still has products cannot be deleted (restrict). Deactivate it instead.
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description', 300)->nullable();
            $table->longText('description')->nullable();
            $table->string('fragrance_family', 60)->nullable()->index();
            $table->text('top_notes')->nullable();
            $table->text('heart_notes')->nullable();
            $table->text('base_notes')->nullable();
            $table->string('longevity', 120)->nullable();   // only shown when the owner supplies it
            $table->string('projection', 120)->nullable();  // only shown when the owner supplies it
            $table->json('specifications')->nullable();     // [{"label": "...", "value": "..."}]
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_bestseller')->default(false);
            $table->string('status', 16)->default('draft'); // draft | active | archived
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->text('owner_notes')->nullable();         // "still missing: price, photos..." reminders
            $table->unsignedInteger('sales_count')->default(0); // popularity sort
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'is_featured']);
            $table->index(['status', 'is_bestseller']);
            $table->index(['status', 'created_at']);
            $table->index('name');
            $table->index('sales_count');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);                       // "8 ml"
            $table->unsignedSmallInteger('size_ml')->nullable();
            $table->string('sku', 64)->unique();
            $table->decimal('price', 10, 2)->nullable();      // null = price not confirmed yet (not purchasable)
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
            $table->index('stock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
