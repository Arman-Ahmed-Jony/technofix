<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Flash sales / Happy Hour: while a campaign runs (start → end, switched on), its products sell at the deal price
| everywhere — cards, product page, cart, checkout (App\Support\Deals + Product::sale_price).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);                    // e.g. Happy Hour, Eid Flash Sale
            $table->string('headline', 160)->nullable();   // e.g. Today’s hottest prices
            $table->string('badge', 30)->default('Flash sale');
            $table->dateTime('starts_at'); // dateTime: MySQL 5.7 rejects two NOT NULL timestamps without defaults
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_home')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('campaign_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('deal_price');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['campaign_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_products');
        Schema::dropIfExists('campaigns');
    }
};
