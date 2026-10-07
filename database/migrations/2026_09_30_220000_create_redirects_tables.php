<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Redirects for addresses that no longer exist (old website links, renamed or deleted products) and a log of
| addresses people and search engines tried that don't exist ("Broken links"). See App\Support\NotFound.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique(); // "/old-product", "/index.php?route=…"; ending in * = everything under it
            $table->string('to_url', 500);          // "/laptop" or a full link; a * is replaced by the rest of the old address
            $table->unsignedSmallInteger('status')->default(301);
            $table->boolean('is_prefix')->default(false)->index();
            $table->boolean('is_active')->default(true);
            $table->string('note')->nullable();
            $table->unsignedInteger('hits')->default(0);
            $table->dateTime('last_hit_at')->nullable();
            $table->timestamps();
        });

        Schema::create('not_found_urls', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();
            $table->unsignedInteger('hits')->default(0);
            $table->unsignedInteger('bot_hits')->default(0);
            $table->string('referrer', 500)->nullable(); // the last page that linked here
            $table->string('user_agent')->nullable();
            $table->dateTime('first_seen_at');
            $table->dateTime('last_seen_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_found_urls');
        Schema::dropIfExists('redirects');
    }
};
