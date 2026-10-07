<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Orders (money in whole taka), their items as a snapshot of the product at order time, the status history and a
| stock ledger (every change to a product's quantity, from orders or staff).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();                   // TF-260929-001
            $table->string('status', 20)->default('pending')->index();
            $table->string('payment_method', 20)->default('cod');
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('source', 20)->default('web');             // web, phone, facebook, whatsapp, walk_in, other

            // Customer (customer accounts link here later)
            $table->string('name', 120);
            $table->string('phone', 20)->index();                    // 01XXXXXXXXX
            $table->string('email', 120)->nullable();

            // Delivery (snapshot of the option chosen at checkout)
            $table->string('delivery_code', 40);
            $table->string('delivery_label', 80);
            $table->string('delivery_eta', 60)->nullable();
            $table->boolean('is_pickup')->default(false);
            $table->string('district', 60)->nullable();
            $table->string('area', 80)->nullable();
            $table->string('address')->nullable();
            $table->string('courier', 60)->nullable();
            $table->string('tracking_code', 80)->nullable();

            // Money
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('total');

            $table->text('customer_note')->nullable();
            $table->text('staff_note')->nullable();

            $table->boolean('stock_deducted')->default(false);      // true while the items' quantities are taken from stock
            $table->string('fingerprint', 64)->nullable()->index();  // catches double submits of the same cart

            // Where the customer came from (utm_*, fbclid, gclid, referrer, landing page)
            $table->json('attribution')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku', 40)->nullable();
            $table->string('warranty', 60)->nullable();
            $table->unsignedInteger('price');
            $table->unsignedSmallInteger('qty');
            $table->unsignedInteger('total');
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null = customer / system
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('change');                 // −2 sold, +2 cancelled, +10 restocked…
            $table->integer('balance');                // quantity after the change
            $table->string('type', 20);                // order, cancelled, adjustment
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
