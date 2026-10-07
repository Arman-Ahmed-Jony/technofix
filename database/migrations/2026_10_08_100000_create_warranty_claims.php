<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Warranty claims (Sales → Warranty claims), the columns of the shop's "Warranty List" sheet plus the customer's
 * email and notes. Customers look theirs up with their mobile number (/warranty); `is_internal` rows (the shop's own
 * stock) never show there. `number` is the sheet's NO — not unique, the old sheet repeats some.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->index();
            $table->date('received_on')->nullable();
            $table->string('customer_name', 120);
            $table->string('customer_phone', 20)->nullable()->index();
            $table->string('customer_email')->nullable();
            $table->boolean('is_internal')->default(false);
            $table->string('product');
            $table->unsignedSmallInteger('qty')->default(1);
            $table->string('serial', 120)->nullable();
            $table->text('problem')->nullable();
            $table->string('vendor', 120)->nullable();
            $table->date('claimed_on')->nullable();
            $table->date('returned_on')->nullable();
            $table->string('new_serial', 120)->nullable();
            $table->string('status', 20)->default('received')->index();
            $table->text('customer_note')->nullable();
            $table->text('staff_note')->nullable();
            $table->dateTime('status_changed_at')->nullable();
            $table->timestamps();
        });

        // What the customer was told, when (timeline on the website + emails sent).
        Schema::create('warranty_claim_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warranty_claim_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->text('note')->nullable();
            $table->dateTime('emailed_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Its own staff area; roles that handle orders get it, so the counter staff keep working.
        foreach (DB::table('roles')->whereNotNull('areas')->get(['id', 'areas']) as $role) {
            $areas = json_decode($role->areas, true) ?: [];
            if (in_array('orders', $areas, true) && ! in_array('warranty', $areas, true)) {
                DB::table('roles')->where('id', $role->id)->update(['areas' => json_encode([...$areas, 'warranty'])]);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('roles')->whereNotNull('areas')->get(['id', 'areas']) as $role) {
            $areas = array_values(array_diff(json_decode($role->areas, true) ?: [], ['warranty']));
            DB::table('roles')->where('id', $role->id)->update(['areas' => json_encode($areas)]);
        }

        Schema::dropIfExists('warranty_claim_events');
        Schema::dropIfExists('warranty_claims');
    }
};
