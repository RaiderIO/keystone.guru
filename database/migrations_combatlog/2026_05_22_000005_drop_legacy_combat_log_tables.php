<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('combat_log_npc_spell_assignments');
        Schema::dropIfExists('combat_log_spell_updates');
    }

    public function down(): void
    {
        Schema::create('combat_log_npc_spell_assignments', function (Blueprint $table) {
            $table->id();
            $table->integer('npc_id');
            $table->integer('spell_id');
            $table->string('combat_log_path');
            $table->text('raw_event');
            $table->timestamps();

            $table->index(['npc_id', 'spell_id']);
            $table->index('spell_id');
        });

        Schema::create('combat_log_spell_updates', function (Blueprint $table) {
            $table->id();
            $table->integer('spell_id');
            $table->json('before')->nullable();
            $table->json('after');
            $table->string('combat_log_path');
            $table->text('raw_event');
            $table->timestamps();

            $table->index('spell_id');
        });
    }
};
