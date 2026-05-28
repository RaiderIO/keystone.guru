<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('combat_log_npc_events', function (Blueprint $table) {
            $table->id();
            $table->integer('npc_id');
            // One of: characteristic_added, characteristic_removed, spell_assigned
            $table->string('event_type');
            // Full class name of the related model (e.g. Characteristic::class, Spell::class)
            $table->string('model_class');
            $table->integer('model_id');
            // Null for system-generated events (e.g. removal by staleness job)
            $table->string('combat_log_path')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['npc_id', 'created_at'], 'clne_npc_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combat_log_npc_events');
    }
};
