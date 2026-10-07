<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Staff roles (App\Enums\StaffArea lists the areas a role can open) and what staff accounts need: a role, on/off,
| last sign-in and the two-step sign-in secret. Starter roles are created here; existing accounts become owners.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('description')->nullable();
            $table->json('areas')->nullable();
            $table->boolean('is_owner')->default(false); // full access; can't be changed or deleted
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('password')->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('role_id');
            $table->dateTime('last_login_at')->nullable()->after('is_active');
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
        });

        $now = now();
        $roles = [
            ['Owner', 'Full access, including staff accounts and every setting.', null, true],
            ['Manager', 'Runs the shop day to day — everything except staff accounts and store settings.', ['orders', 'leads', 'customers', 'marketing', 'catalog', 'content', 'seo', 'reports'], false],
            ['Sales & orders', 'Takes and confirms orders and calls back leads.', ['orders', 'leads', 'customers'], false],
            ['Catalog', 'Adds and edits products, prices, stock and photos.', ['catalog'], false],
            ['Marketing', 'Offers, banners, home page, SEO and pixels.', ['marketing', 'content', 'seo', 'leads'], false],
        ];

        foreach ($roles as $i => [$name, $description, $areas, $owner]) {
            DB::table('roles')->insert([
                'name' => $name, 'description' => $description, 'areas' => $areas ? json_encode($areas) : null,
                'is_owner' => $owner, 'sort' => $i, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        DB::table('users')->update(['role_id' => DB::table('roles')->where('is_owner', true)->value('id')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['is_active', 'last_login_at', 'app_authentication_secret', 'app_authentication_recovery_codes']);
        });
        Schema::dropIfExists('roles');
    }
};
