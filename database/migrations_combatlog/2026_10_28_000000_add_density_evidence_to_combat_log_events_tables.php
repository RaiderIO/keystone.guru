<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Appended without `after()`, so MySQL can add the nullable columns instantly on these append-only tables.
     */
    public function up(): void
    {
        Schema::table('combat_log_npc_events', function (Blueprint $table) {
            $table->unsignedSmallInteger('fact_days')->nullable();
            $table->unsignedSmallInteger('subject_days')->nullable();
        });

        Schema::table('combat_log_spell_events', function (Blueprint $table) {
            $table->unsignedSmallInteger('fact_days')->nullable();
            $table->unsignedSmallInteger('subject_days')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('combat_log_npc_events', function (Blueprint $table) {
            $table->dropColumn(['fact_days', 'subject_days']);
        });

        Schema::table('combat_log_spell_events', function (Blueprint $table) {
            $table->dropColumn(['fact_days', 'subject_days']);
        });
    }
};
