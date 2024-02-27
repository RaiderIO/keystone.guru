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
        Schema::create('season_dungeons', function (Blueprint $table) {
            $table->id();
            $table->integer('season_id');
            $table->integer('dungeon_id');
            $table->index(['season_id', 'dungeon_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('season_dungeons');
    }
};
