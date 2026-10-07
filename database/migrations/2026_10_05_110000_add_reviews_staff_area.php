<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reviews & questions became their own staff area (before, they came with Catalog). Roles that had Catalog keep
 * them, so nobody loses access they had.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('roles')->whereNotNull('areas')->get(['id', 'areas']) as $role) {
            $areas = json_decode($role->areas, true) ?: [];
            if (in_array('catalog', $areas, true) && ! in_array('reviews', $areas, true)) {
                DB::table('roles')->where('id', $role->id)->update(['areas' => json_encode([...$areas, 'reviews'])]);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('roles')->whereNotNull('areas')->get(['id', 'areas']) as $role) {
            $areas = array_values(array_diff(json_decode($role->areas, true) ?: [], ['reviews']));
            DB::table('roles')->where('id', $role->id)->update(['areas' => json_encode($areas)]);
        }
    }
};
