<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Hand-picked links between products: "related" (alternatives, shown in "You may also like") and
| "accessory" (add-ons, shown as "Accessories for this product"). One direction only: A → B.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_relations', function (Blueprint $table) {
            $table->id(); // insertion order = display order
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_id')->constrained('products')->cascadeOnDelete();
            $table->string('type', 20); // related | accessory
            $table->unique(['product_id', 'type', 'related_id']);
            $table->index('related_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_relations');
    }
};
