<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| PC Builder slots (Processor, Motherboard, RAM…): which categories fill each one, and the role it plays in the
| compatibility checks (App\Support\PcBuilder\Compatibility). Rules are switched on/off in PcBuilderSettings.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pc_builder_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('description', 160)->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('section', 20)->default('core');    // core | peripheral
            $table->string('role', 30)->nullable()->unique();  // App\Enums\BuilderRole (one slot per role)
            $table->json('category_ids');                     // products from these categories and their sub-categories
            $table->foreignId('filter_attribute_id')->nullable()->constrained('attributes')->nullOnDelete();
            $table->json('filter_values')->nullable();         // e.g. RAM: Form Factor = Desktop (DIMM)
            $table->boolean('is_required')->default(false);
            $table->unsignedTinyInteger('max_qty')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pc_builder_slots');
    }
};
