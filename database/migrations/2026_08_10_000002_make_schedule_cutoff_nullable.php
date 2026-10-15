<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE schedules MODIFY cutoff DATE NULL');
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        DB::statement("UPDATE schedules SET cutoff = etd WHERE cutoff IS NULL");
        DB::statement('ALTER TABLE schedules MODIFY cutoff DATE NOT NULL');
    }
};
