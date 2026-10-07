<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Every payment and refund on an order (cash collected on delivery, bKash advances recorded by staff, and later
| online gateway transactions). orders.paid_amount is the running total of completed payments minus refunds.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20);                     // App\Enums\PaymentMethod
            $table->boolean('is_refund')->default(false);
            $table->string('status', 20)->default('completed'); // App\Enums\TransactionStatus
            $table->unsignedInteger('amount');                // whole taka, always positive
            $table->string('trx_id', 80)->nullable()->index(); // bKash/Nagad TrxID, bank reference, gateway id
            $table->string('note')->nullable();
            $table->json('payload')->nullable();              // raw gateway response
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // staff who recorded it
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->integer('paid_amount')->default(0)->after('total');
        });

        // Orders marked paid before payments were recorded get one payment for their total.
        foreach (DB::table('orders')->where('payment_status', 'paid')->get() as $order) {
            DB::table('payments')->insert([
                'order_id' => $order->id, 'method' => $order->payment_method, 'amount' => $order->total,
                'note' => 'Recorded when payments were added', 'status' => 'completed',
                'paid_at' => $order->updated_at, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('orders')->where('id', $order->id)->update(['paid_amount' => $order->total]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('paid_amount'));
        Schema::dropIfExists('payments');
    }
};
