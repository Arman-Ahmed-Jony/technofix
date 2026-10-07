<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| "Notify me when it's back" requests from product pages. Each one is also a lead (Admin → Leads) for staff to call;
| customers who gave an email get a message automatically when the product can be ordered again.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100)->nullable();
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
