<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('customs_status')->default('pending')->after('custom_charge_amount');
            $table->string('customs_reference')->nullable()->after('customs_status');
            $table->string('customs_declaration_no')->nullable()->after('customs_reference');
            $table->string('customs_bayan_no')->nullable()->after('customs_declaration_no');
            $table->double('customs_fees')->default(0)->after('customs_bayan_no');
            $table->json('customs_documents')->nullable()->after('customs_fees');
            $table->date('customs_cleared_at')->nullable()->after('customs_documents');
            $table->text('customs_notes')->nullable()->after('customs_cleared_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'customs_status',
                'customs_reference',
                'customs_declaration_no',
                'customs_bayan_no',
                'customs_fees',
                'customs_documents',
                'customs_cleared_at',
                'customs_notes',
            ]);
        });
    }
};
