<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Staff-set order of Featured products (Products → Arrange featured); null = not featured. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('featured_sort')->nullable()->after('is_featured');
        });

        // Keep today's order (latest edited first) as the starting point.
        DB::table('products')->where('is_featured', true)->orderByDesc('updated_at')->orderByDesc('id')->pluck('id')
            ->each(fn ($id, $i) => DB::table('products')->where('id', $id)->update(['featured_sort' => $i + 1]));
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('featured_sort');
        });
    }
};
