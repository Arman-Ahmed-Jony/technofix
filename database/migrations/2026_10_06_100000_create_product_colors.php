<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional color versions of a product (customer picks one on the product page) and the chosen color on order lines.
 * Price and photos stay the product's; each color can be marked sold out. Stock is still counted per product.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40);
            $table->string('hex', 7)->nullable();
            $table->boolean('is_available')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('color', 40)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('color'));
        Schema::dropIfExists('product_colors');
    }
};
