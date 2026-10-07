<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Inventory: suppliers, stock-in receipts (goods arriving, with cost) and reasons on stock movements.
| See App\Services\InventoryService.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('contact_person', 120)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();          // SI-261003-001
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference', 80)->nullable();      // the supplier's invoice / challan number
            $table->date('received_on');
            $table->unsignedInteger('total_cost')->default(0);
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty');
            $table->unsignedInteger('unit_cost');
            $table->unsignedInteger('total');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('stock_receipt_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            $table->string('reason', 30)->nullable()->after('type');
        });

        // Storekeepers: managers and the catalog team get the new "Inventory" area.
        foreach (DB::table('roles')->whereIn('name', ['Manager', 'Catalog'])->get() as $role) {
            DB::table('roles')->where('id', $role->id)->update([
                'areas' => json_encode(array_values(array_unique([...json_decode((string) $role->areas, true) ?: [], 'inventory']))),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_receipt_id');
            $table->dropColumn('reason');
        });
        Schema::dropIfExists('stock_receipt_items');
        Schema::dropIfExists('stock_receipts');
        Schema::dropIfExists('suppliers');
    }
};
