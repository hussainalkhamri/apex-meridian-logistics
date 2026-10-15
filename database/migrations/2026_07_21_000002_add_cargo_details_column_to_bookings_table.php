<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bookings', 'cargo_details')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->json('cargo_details')->nullable()->after('documents');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'cargo_details')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('cargo_details');
            });
        }
    }
};
