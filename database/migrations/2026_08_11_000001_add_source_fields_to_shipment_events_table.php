<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_events', function (Blueprint $table) {
            if (! Schema::hasColumn('shipment_events', 'source_name')) {
                $table->string('source_name')->nullable()->after('status');
            }

            if (! Schema::hasColumn('shipment_events', 'source_type')) {
                $table->string('source_type')->nullable()->after('source_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipment_events', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_events', 'source_type')) {
                $table->dropColumn('source_type');
            }

            if (Schema::hasColumn('shipment_events', 'source_name')) {
                $table->dropColumn('source_name');
            }
        });
    }
};
