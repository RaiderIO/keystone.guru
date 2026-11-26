<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('combat_log_encounter_observations', function (Blueprint $table) {
            $table->id();
            $table->integer('dungeon_id');
            $table->integer('encounter_id');
            $table->string('run_key');
            $table->string('combat_log_path');
            $table->integer('key_level')->nullable();
            $table->integer('affix_group_id')->nullable();
            $table->boolean('success');
            $table->integer('duration_ms');
            $table->json('data');
            $table->timestamps();

            $table->index(['dungeon_id', 'encounter_id'], 'cleo_dungeon_encounter_index');
            $table->index('run_key', 'cleo_run_key_index');
            $table->index('created_at', 'cleo_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combat_log_encounter_observations');
    }
};
