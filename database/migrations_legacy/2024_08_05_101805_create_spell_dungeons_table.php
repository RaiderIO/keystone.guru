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
        Schema::create('spell_dungeons', function (Blueprint $table) {
            $table->id();
            $table->integer('spell_id');
            $table->integer('dungeon_id');

            $table->index('spell_id');
            $table->index('dungeon_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spell_dungeons');
    }
};
