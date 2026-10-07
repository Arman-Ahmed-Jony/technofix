<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer reviews (stars + comment, approved by staff) and questions (answered by staff) on product pages.
 * Only signed-in customers can write them. products.rating_avg / rating_count are kept in step by
 * App\Services\ProductFeedback so product cards don't need to count reviews.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            // The delivered order that makes it a "verified purchase" (null = not bought here).
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->string('status', 20)->default('pending')->index();
            $table->text('reply')->nullable();
            $table->dateTime('replied_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'customer_id']);
            $table->index(['product_id', 'status']);
        });

        Schema::create('product_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('answered_at')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('rating_avg', 2, 1)->nullable()->after('stock_qty');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dateTime('review_requested_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('review_requested_at'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['rating_avg', 'rating_count']));
        Schema::dropIfExists('product_questions');
        Schema::dropIfExists('product_reviews');
    }
};
