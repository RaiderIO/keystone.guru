<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('npc_encounter_mechanics', function (Blueprint $table) {
            $table->id();
            $table->integer('dungeon_id');
            $table->integer('encounter_id');
            $table->string('type');
            $table->integer('npc_id')->nullable();
            $table->integer('spell_id')->nullable();
            $table->integer('first_ms')->nullable();
            $table->decimal('first_health_pct', 5, 1)->nullable();
            $table->integer('cooldown_ms')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->integer('count')->nullable();
            $table->integer('run_count');
            $table->integer('total_run_count');
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['dungeon_id', 'encounter_id'], 'nem_dungeon_encounter_index');
            $table->index('npc_id', 'nem_npc_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('npc_encounter_mechanics');
    }
};
