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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combat_log_npc_spell_assignments');
    }
};