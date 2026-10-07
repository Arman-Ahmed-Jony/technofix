<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Incomplete checkouts: what a visitor typed at checkout (once the mobile number is valid). Left without an order,
| a draft becomes an "Incomplete checkout" lead for staff to call (App\Services\IncompleteCheckouts).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('token', 40)->unique(); // the "finish your order" link sent to the customer
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120)->nullable();
            $table->string('phone', 20)->index();
            $table->string('email')->nullable();
            $table->string('delivery_code', 40)->nullable();
            $table->string('district', 60)->nullable();
            $table->string('area', 80)->nullable();
            $table->string('address')->nullable();
            $table->string('note', 500)->nullable();
            $table->json('items'); // [{id, name, price, qty}] priced from the catalog when saved
            $table->unsignedInteger('subtotal')->default(0);
            $table->json('attribution')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('captured_at')->nullable(); // when it was last copied to its lead
            $table->timestamps();

            $table->index(['order_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_drafts');
    }
};
