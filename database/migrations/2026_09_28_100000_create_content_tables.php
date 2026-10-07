<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Admin-managed content: static pages (footer/policies), FAQs, repair & IT services,
| and the leads inbox that collects contact and service requests.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 300)->nullable();
            $table->longText('content')->nullable();
            $table->string('footer_group', 20)->nullable()->index(); // help | company | null
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('group', 60)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 50)->default('tool');
            $table->string('summary', 300);
            $table->longText('content')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->index();
            $table->string('status', 20)->default('new')->index();
            $table->string('name', 100);
            $table->string('phone', 20)->index();
            $table->string('email')->nullable();
            $table->string('subject', 150)->nullable();
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->string('page_url', 500)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('services');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('pages');
    }
};
