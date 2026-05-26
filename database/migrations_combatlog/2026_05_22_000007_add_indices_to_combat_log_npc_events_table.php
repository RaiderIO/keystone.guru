<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('combat_log_npc_events', function (Blueprint $table) {
            // Lookup events by related model (e.g. all events for Characteristic X across all NPCs)
            $table->index(['model_class', 'model_id'], 'clne_model_class_model_id_index');
            // Lookup whether a specific (npc, model) pair already has an event, and for type-filtered feed queries
            $table->index(['npc_id', 'model_class', 'model_id'], 'clne_npc_model_class_model_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('combat_log_npc_events', function (Blueprint $table) {
            $table->dropIndex('clne_model_class_model_id_index');
            $table->dropIndex('clne_npc_model_class_model_id_index');
        });
    }
};
