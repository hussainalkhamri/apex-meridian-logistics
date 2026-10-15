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
        Schema::create('invoices', function (Blueprint $table) {
            $table->string('id')->primary(); // INV-2026-10001
            $table->string('booking_id');
            $table->double('amount');
            $table->string('currency')->default('USD');
            $table->string('status')->default('غير مدفوعة'); // غير مدفوعة, مدفوعة جزئياً, مدفوعة
            $table->timestamp('issued_at');
            $table->timestamp('due_at');
            $table->timestamps();
            
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
