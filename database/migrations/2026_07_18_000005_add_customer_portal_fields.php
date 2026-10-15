<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'password')) {
                $table->string('password')->nullable()->after('email');
            }

            if (! Schema::hasColumn('customers', 'portal_enabled')) {
                $table->boolean('portal_enabled')->default(true)->after('password');
            }

            if (! Schema::hasColumn('customers', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('portal_enabled');
            }

            if (! Schema::hasColumn('customers', 'remember_token')) {
                $table->rememberToken()->after('last_login_at');
            }

            $table->index('email', 'customers_email_portal_index');
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'customer_decision')) {
                $table->string('customer_decision')->nullable()->after('sales_notes');
            }

            if (! Schema::hasColumn('bookings', 'customer_decision_note')) {
                $table->text('customer_decision_note')->nullable()->after('customer_decision');
            }

            if (! Schema::hasColumn('bookings', 'customer_decided_at')) {
                $table->timestamp('customer_decided_at')->nullable()->after('customer_decision_note');
            }
        });

        Schema::create('customer_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->string('notification_id');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();

            $table->foreign('notification_id')->references('id')->on('notifications')->cascadeOnDelete();
            $table->unique(['notification_id', 'customer_id'], 'customer_notification_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notification_reads');

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'customer_decided_at')) {
                $table->dropColumn('customer_decided_at');
            }

            if (Schema::hasColumn('bookings', 'customer_decision_note')) {
                $table->dropColumn('customer_decision_note');
            }

            if (Schema::hasColumn('bookings', 'customer_decision')) {
                $table->dropColumn('customer_decision');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_email_portal_index');

            if (Schema::hasColumn('customers', 'remember_token')) {
                $table->dropRememberToken();
            }

            if (Schema::hasColumn('customers', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }

            if (Schema::hasColumn('customers', 'portal_enabled')) {
                $table->dropColumn('portal_enabled');
            }

            if (Schema::hasColumn('customers', 'password')) {
                $table->dropColumn('password');
            }
        });
    }
};
