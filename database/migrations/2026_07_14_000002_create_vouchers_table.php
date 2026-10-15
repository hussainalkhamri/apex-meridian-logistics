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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->string('id')->primary(); // REC-2026-10001 or PAY-2026-10001
            $table->string('type'); // 'receipt' (سند قبض) or 'payment' (سند صرف)
            $table->string('booking_id')->nullable();
            $table->double('amount');
            $table->string('payment_method'); // كاش, الكريمي, تحويل بنكي, الخ
            $table->string('payee_payer'); // المستلم / المسلم إليه
            $table->text('description'); // البيان / الغرض
            $table->date('date');
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
