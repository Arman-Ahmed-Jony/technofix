<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| PC builds customers keep in their account. Only the build code is stored ("slotId:productId,…"), so a saved build
| always opens with today's prices and compatibility checks.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_builds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('code', 500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_builds');
    }
};
