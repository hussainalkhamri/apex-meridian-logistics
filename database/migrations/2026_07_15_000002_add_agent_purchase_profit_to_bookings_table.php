<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('agent_id')->nullable()->after('pricing_notes')->constrained('agents')->nullOnDelete();
            $table->double('agent_purchase_amount')->default(0)->after('agent_id');
            $table->double('agent_purchase_quantity')->default(0)->after('agent_purchase_amount');
            $table->string('agent_purchase_currency', 3)->default('USD')->after('agent_purchase_quantity');
            $table->double('profit_amount')->default(0)->after('agent_purchase_currency');
            $table->text('agent_purchase_notes')->nullable()->after('profit_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropColumn([
                'agent_id',
                'agent_purchase_amount',
                'agent_purchase_quantity',
                'agent_purchase_currency',
                'profit_amount',
                'agent_purchase_notes',
            ]);
        });
    }
};
