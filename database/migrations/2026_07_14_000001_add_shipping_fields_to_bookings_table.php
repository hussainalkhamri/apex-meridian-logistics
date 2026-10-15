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
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('shipper')->nullable()->after('status');
            $table->text('consignee')->nullable()->after('shipper');
            $table->text('notify_party')->nullable()->after('consignee');
            $table->string('bill_type')->default('TELEX RELEASE')->nullable()->after('notify_party');
            $table->string('type_of_move')->default('CY-CY')->nullable()->after('bill_type');
            $table->string('job_no')->nullable()->after('type_of_move');
            $table->string('overseas_agent')->nullable()->after('job_no');
            $table->string('place_of_delivery')->nullable()->after('overseas_agent');
            $table->string('quantity_packages')->nullable()->after('place_of_delivery');
            $table->double('measurement_cbm')->nullable()->after('quantity_packages');
            $table->text('container_seal_no')->nullable()->after('measurement_cbm');
            $table->text('custom_notes')->nullable()->after('container_seal_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'shipper',
                'consignee',
                'notify_party',
                'bill_type',
                'type_of_move',
                'job_no',
                'overseas_agent',
                'place_of_delivery',
                'quantity_packages',
                'measurement_cbm',
                'container_seal_no',
                'custom_notes'
            ]);
        });
    }
};
