<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ports', function (Blueprint $table) {
            if (! Schema::hasColumn('ports', 'country_code')) {
                $table->string('country_code', 2)->nullable()->after('country');
            }

            if (! Schema::hasColumn('ports', 'country_flag')) {
                $table->string('country_flag', 16)->nullable()->after('country_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ports', function (Blueprint $table) {
            if (Schema::hasColumn('ports', 'country_flag')) {
                $table->dropColumn('country_flag');
            }

            if (Schema::hasColumn('ports', 'country_code')) {
                $table->dropColumn('country_code');
            }
        });
    }
};
