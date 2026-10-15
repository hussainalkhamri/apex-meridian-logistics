<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_insights', function (Blueprint $table) {
            $table->string('source_key')->nullable()->unique()->after('id');
            $table->string('target', 50)->default('general')->after('level');
            $table->text('action_url')->nullable()->after('target');
            $table->string('action_label')->nullable()->after('action_url');
            $table->string('notification_id')->nullable()->after('action_label');
            $table->timestamp('last_seen_at')->nullable()->after('notification_id');

            $table->foreign('notification_id')->references('id')->on('notifications')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('system_insights', function (Blueprint $table) {
            $table->dropForeign(['notification_id']);
            $table->dropUnique(['source_key']);
            $table->dropColumn([
                'source_key',
                'target',
                'action_url',
                'action_label',
                'notification_id',
                'last_seen_at',
            ]);
        });
    }
};
