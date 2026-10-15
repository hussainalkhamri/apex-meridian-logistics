<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->string('notification_id');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();

            $table->foreign('notification_id')->references('id')->on('notifications')->cascadeOnDelete();
            $table->unique(['notification_id', 'user_id']);
        });

        $readNotificationIds = DB::table('notifications')->where('read', true)->pluck('id');
        $userIds = DB::table('users')->pluck('id');

        if ($readNotificationIds->isNotEmpty() && $userIds->isNotEmpty()) {
            $now = now();
            $rows = [];

            foreach ($readNotificationIds as $notificationId) {
                foreach ($userIds as $userId) {
                    $rows[] = [
                        'notification_id' => $notificationId,
                        'user_id' => $userId,
                        'read_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('notification_reads')->insertOrIgnore($chunk);
            }

            DB::table('notifications')->where('read', true)->update(['read' => false]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
    }
};
