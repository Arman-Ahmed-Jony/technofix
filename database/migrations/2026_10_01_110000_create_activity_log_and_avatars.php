<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Activity log: who changed what in the admin (App\Support\ActivityLog), and staff profile photos.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 100)->nullable(); // kept when the account is deleted
            $table->string('event', 30);                  // created | updated | deleted | settings | imported | login
            $table->string('subject_type')->nullable();   // model class, or "settings"
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();  // name / number at the time
            $table->json('changes')->nullable();          // [{field, old, new}]
            $table->string('ip', 45)->nullable();
            $table->dateTime('created_at')->index();

            $table->index(['subject_type', 'subject_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('email');
        });

        // Managers see the log too.
        $manager = DB::table('roles')->where('name', 'Manager')->first();
        if ($manager) {
            DB::table('roles')->where('id', $manager->id)->update([
                'areas' => json_encode(array_values(array_unique([...json_decode((string) $manager->areas, true) ?: [], 'activity']))),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('avatar_path'));
        Schema::dropIfExists('activity_log');
    }
};
