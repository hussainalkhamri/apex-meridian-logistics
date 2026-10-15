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
        Schema::create('shipment_tracks', function (Blueprint $table) {
            $table->string('id')->primary(); // BHRL-2026-104587
            $table->string('booking_id');
            $table->string('status')->default('تم الحجز'); // تم الحجز, قيد التحميل, قيد الإبحار, تم التفريغ, تم التسليم
            $table->integer('progress')->default(10);
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_tracks');
    }
};
