<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the "Scrolling notice" section right under the hero on existing home pages (new installs get it from
 * HomeSectionSeeder). Edit or switch it off in Admin → Content → Home page.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('home_sections')->exists() || DB::table('home_sections')->where('type', 'ticker')->exists()) {
            return;
        }

        $heroSort = DB::table('home_sections')->where('type', 'hero')->value('sort') ?? -1;
        DB::table('home_sections')->where('sort', '>', $heroSort)->increment('sort');

        DB::table('home_sections')->insert([
            'type' => 'ticker',
            'settings' => json_encode(['label' => 'Notice', 'icon' => 'speakerphone', 'speed' => 'normal', 'messages' => [
                ['text' => 'Cash on delivery all over Bangladesh — order online and pay when it arrives.'],
                ['text' => 'Genuine products with official brand warranty.'],
            ]]),
            'is_active' => true,
            'sort' => $heroSort + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('home_sections')->where('type', 'ticker')->delete();
    }
};
