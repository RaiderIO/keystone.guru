<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('combat_log_spell_events', function (Blueprint $table) {
            $table->id();
            $table->integer('spell_id');
            // One of: spell_created, property_changed, property_removed
            $table->string('event_type');
            $table->json('before')->nullable();
            $table->json('after');
            // Null for system-generated events (e.g. removal by staleness job)
            $table->string('combat_log_path')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['spell_id', 'created_at'], 'clse_spell_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combat_log_spell_events');
    }
};
