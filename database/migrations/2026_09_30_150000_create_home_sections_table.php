<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| The home page, block by block (Admin → Content → Home page). Each type has its own settings (JSON); the order,
| headings and on/off are the owner's. Rendered by App\Support\HomePage + resources/views/storefront/home/*.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);
            $table->string('eyebrow', 80)->nullable();
            $table->string('title', 160)->nullable();
            $table->string('subtitle', 300)->nullable();
            $table->text('body')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};
