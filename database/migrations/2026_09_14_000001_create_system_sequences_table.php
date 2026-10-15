<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 20);
            $table->unsignedInteger('year');
            $table->unsignedInteger('next_number')->default(1);
            $table->timestamps();
            $table->unique(['prefix', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_sequences');
    }
};
