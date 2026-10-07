<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Coupon codes (whole-order discounts). Uses are counted from orders that aren't cancelled or returned.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();              // stored upper-case
            $table->string('description')->nullable();         // staff note, e.g. "Eid campaign"
            $table->string('type', 20);                        // percent | fixed | free_delivery
            $table->unsignedInteger('value')->default(0);      // % or taka
            $table->unsignedInteger('max_discount')->nullable(); // cap for percent coupons (taka)
            $table->unsignedInteger('min_subtotal')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();        // total uses
            $table->unsignedInteger('per_customer_limit')->nullable(); // uses per mobile number
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('discount')->constrained()->nullOnDelete();
            $table->string('coupon_code', 40)->nullable()->after('coupon_id'); // kept even if the coupon is deleted
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn('coupon_code');
        });
        Schema::dropIfExists('coupons');
    }
};
