<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('enemy_active_auras');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('enemy_active_auras', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('enemy_id');
            $table->integer('spell_id');

            $table->index('enemy_id');
            $table->index('spell_id');
        });
    }
};
