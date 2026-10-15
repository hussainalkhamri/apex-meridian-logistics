<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('shipping_type');
            $table->string('service_type');
            $table->string('container_type');
            $table->string('origin_country');
            $table->string('origin_port');
            $table->string('destination_country');
            $table->string('destination_port');
            $table->string('shipping_company');
            $table->unsignedInteger('transit_time')->nullable();
            $table->string('currency', 10);
            $table->decimal('price', 12, 2);
            $table->decimal('additional_fee', 12, 2)->nullable();
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
