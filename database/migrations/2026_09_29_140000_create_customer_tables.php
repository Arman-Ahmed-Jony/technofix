<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Storefront customer accounts — separate from staff (users) with their own guard, password resets and addresses.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 20)->unique();   // 01XXXXXXXXX — the login id (not verified until SMS arrives)
            $table->string('email', 120)->nullable()->unique();
            $table->string('password');
            $table->rememberToken();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 40)->nullable();  // Home, Office…
            $table->string('name', 120);
            $table->string('phone', 20);
            $table->string('district', 60);
            $table->string('area', 80)->nullable();
            $table->string('address');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('source')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });
        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
