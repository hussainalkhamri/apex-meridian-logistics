<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('invoice_id')->nullable()->after('booking_id');
            $table->double('paid_before')->default(0)->after('amount');
            $table->double('paid_after')->default(0)->after('paid_before');
            $table->double('balance_after')->default(0)->after('paid_after');

            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn(['invoice_id', 'paid_before', 'paid_after', 'balance_after']);
        });
    }
};
