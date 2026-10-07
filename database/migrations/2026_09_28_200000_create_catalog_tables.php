<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Catalog: brands, category tree, specification fields (attributes) with per-category
| spec templates, products and their spec values. Images use spatie/laravel-medialibrary.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_featured')->default(false)->index(); // shown in the home "brands" strip
            $table->boolean('is_zone')->default(false);              // has an "Exclusive Zone" brand store
            $table->string('zone_tagline', 80)->nullable();
            $table->string('zone_theme', 20)->nullable();            // card colour theme
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 50)->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('depth')->default(0);
            $table->string('tree_path')->default('')->index(); // e.g. "0001-component/0003-motherboard" — sorts the tree in order
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_menu')->default(true);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->timestamps();
        });

        // Brands listed under a category in the mega menu.
        Schema::create('brand_category', function (Blueprint $table) {
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->primary(['brand_id', 'category_id']);
        });

        // Specification fields, shared across categories (e.g. "Memory Type" for RAM and motherboards).
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 60)->unique();   // stable key; PC Builder rules rely on the system codes
            $table->string('unit', 20)->nullable(); // GB, W, MHz…
            $table->string('help', 255)->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // Spec template: which fields a category's products have, grouped and ordered.
        Schema::create('category_attribute', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('group', 60)->default('Specification');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_filterable')->default(false); // shown as a filter on the category page
            $table->boolean('show_on_card')->default(false);  // shown as a spec chip on product cards
            $table->unique(['category_id', 'attribute_id']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku', 40)->unique();
            $table->string('model', 120)->nullable();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->json('key_features')->nullable();
            $table->longText('description')->nullable();
            $table->json('extra_specs')->nullable(); // one-off rows not in the category template
            $table->unsignedInteger('price')->nullable(); // BDT; null = call for price
            $table->unsignedInteger('regular_price')->nullable();
            $table->unsignedInteger('cost_price')->nullable();
            $table->string('stock_status', 20)->default('in_stock')->index();
            $table->integer('stock_qty')->default(0);
            $table->string('warranty', 60)->nullable();
            $table->boolean('emi_available')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_best_seller')->default(false);
            $table->boolean('is_hot_deal')->default(false);
            $table->string('status', 20)->default('published')->index(); // draft | published
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('sold_count')->default(0);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status']);
            $table->index(['brand_id', 'status']);
            $table->index('price');
        });

        Schema::create('product_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->unique(['product_id', 'attribute_id']);
            $table->index(['attribute_id', 'value']); // facet counts / filtering
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_specs');
        Schema::dropIfExists('products');
        Schema::dropIfExists('category_attribute');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('brand_category');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
    }
};
