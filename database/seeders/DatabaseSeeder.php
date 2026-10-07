<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * No users are seeded on purpose: every user is admin staff. Create them with
     * `php artisan make:filament-user`. Model events stay on: categories compute their tree paths on save.
     */
    public function run(): void
    {
        $this->call([ContentSeeder::class, CatalogSeeder::class]);

        // Demo products only outside production (run DemoProductSeeder explicitly to load them there).
        if (! app()->isProduction()) {
            $this->call(DemoProductSeeder::class);
        }

        $this->call([PcBuilderSeeder::class, BannerSeeder::class, HomeSectionSeeder::class]);
    }
}
