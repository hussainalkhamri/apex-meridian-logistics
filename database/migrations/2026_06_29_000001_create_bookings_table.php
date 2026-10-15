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
        Schema::create('bookings', function (Blueprint $table) {
            $table->string('id')->primary(); // BK-2026-1 etc.
            
            // Customer Info
            $table->string('customer_company');
            $table->string('customer_contact_name');
            $table->string('customer_phone');
            $table->string('customer_email');
            
            // Cargo Info
            $table->string('shipment_type'); // FCL or LCL
            $table->string('container_type')->nullable();
            $table->integer('container_qty')->nullable();
            $table->text('goods_description');
            $table->double('weight_kg');
            $table->double('volume_cbm')->default(0);
            $table->boolean('dangerous')->default(false);
            $table->string('documents')->nullable();
            
            // Route
            $table->string('pol');
            $table->string('pod');
            
            // Workflow Status
            $table->string('status')->default('new'); // new, priced, sent, approved, booked, rejected
            $table->text('sales_notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            
            // Pricing Details (1-to-1)
            $table->integer('ocean_freight')->nullable();
            $table->integer('origin_charges')->nullable();
            $table->integer('dthc')->nullable();
            $table->integer('war_risk_surcharge')->nullable();
            $table->integer('other_charges')->nullable();
            $table->integer('transit_days')->nullable();
            $table->date('validity_date')->nullable();
            $table->text('pricing_notes')->nullable();
            $table->string('priced_by')->nullable();
            $table->timestamp('priced_at')->nullable();
            
            // Carrier Booking Confirmation (1-to-1)
            $table->string('carrier_vessel')->nullable();
            $table->string('carrier_voyage')->nullable();
            $table->string('carrier_booking_ref')->nullable();
            $table->date('carrier_etd')->nullable();
            $table->date('carrier_eta')->nullable();
            $table->string('carrier_booked_by')->nullable();
            $table->timestamp('carrier_booked_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
