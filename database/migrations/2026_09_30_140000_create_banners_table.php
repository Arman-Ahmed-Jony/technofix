<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Home hero slides and the announcement bar (Admin → Content → Banners). Pictures via medialibrary
| (collections: picture, desktop, mobile).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('placement', 20);                 // hero | announcement
            $table->string('style', 20)->default('designed'); // hero: designed | image
            $table->string('eyebrow', 60)->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('title', 160);
            $table->string('highlight', 80)->nullable();      // words of the title shown in the brand gradient
            $table->string('text', 300)->nullable();
            $table->string('button_label', 40)->nullable();
            $table->string('button_url', 500)->nullable();
            $table->string('button2_label', 40)->nullable();
            $table->string('button2_url', 500)->nullable();
            $table->string('visual', 20)->nullable();         // built-in illustration, "picture" or null
            $table->string('color', 20)->default('blue');     // glow (hero) / bar colour (announcement)
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['placement', 'is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
