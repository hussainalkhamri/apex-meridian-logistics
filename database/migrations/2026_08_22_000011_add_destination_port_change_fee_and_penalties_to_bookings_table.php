<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->double('destination_port_change_fee')->default(0)->after('other_charges');
            $table->double('other_penalties')->default(0)->after('destination_port_change_fee');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['destination_port_change_fee', 'other_penalties']);
        });
    }
};
