<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('schedules', 'carrier')) {
                $table->string('carrier')->nullable()->after('voyage');
            }

            if (! Schema::hasColumn('schedules', 'service_name')) {
                $table->string('service_name')->nullable()->after('carrier');
            }

            if (! Schema::hasColumn('schedules', 'origin_code')) {
                $table->string('origin_code', 20)->nullable()->after('origin');
            }

            if (! Schema::hasColumn('schedules', 'destination_code')) {
                $table->string('destination_code', 20)->nullable()->after('destination');
            }

            if (! Schema::hasColumn('schedules', 'status')) {
                $table->string('status', 30)->default('published')->after('cutoff');
            }

            if (! Schema::hasColumn('schedules', 'is_published')) {
                $table->boolean('is_published')->default(true)->after('status');
            }

            if (! Schema::hasColumn('schedules', 'source_name')) {
                $table->string('source_name')->nullable()->after('is_published');
            }

            if (! Schema::hasColumn('schedules', 'source_url')) {
                $table->string('source_url', 1000)->nullable()->after('source_name');
            }

            if (! Schema::hasColumn('schedules', 'source_reference')) {
                $table->string('source_reference')->nullable()->after('source_url');
            }

            if (! Schema::hasColumn('schedules', 'source_checked_at')) {
                $table->date('source_checked_at')->nullable()->after('source_reference');
            }

            if (! Schema::hasColumn('schedules', 'booking_url')) {
                $table->string('booking_url', 1000)->nullable()->after('source_checked_at');
            }

            if (! Schema::hasColumn('schedules', 'notes')) {
                $table->text('notes')->nullable()->after('booking_url');
            }
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->index(['is_published', 'status', 'etd'], 'schedules_public_status_etd_index');
            $table->index(['origin', 'destination', 'etd'], 'schedules_route_etd_index');
        });

        DB::table('schedules')
            ->whereNull('source_name')
            ->update([
                'status' => 'draft',
                'is_published' => false,
                'source_name' => 'بيانات غير موثقة',
            ]);
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('schedules_public_status_etd_index');
            $table->dropIndex('schedules_route_etd_index');
        });

        Schema::table('schedules', function (Blueprint $table) {
            foreach ([
                'notes',
                'booking_url',
                'source_checked_at',
                'source_reference',
                'source_url',
                'source_name',
                'is_published',
                'status',
                'destination_code',
                'origin_code',
                'service_name',
                'carrier',
            ] as $column) {
                if (Schema::hasColumn('schedules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
