<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Editable "Buying guide" boxes: questions for category pages (their text is categories.description), and
| text + questions + SEO for category + brand pages (/laptop/dell). Empty = the defaults from settings.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->json('faqs')->nullable()->after('description');
        });

        Schema::create('category_brand_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->longText('content')->nullable();
            $table->json('faqs')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->timestamps();
            $table->unique(['category_id', 'brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_brand_pages');

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('faqs');
        });
    }
};
