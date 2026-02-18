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
        Schema::table('kill_zone_enemies', function (Blueprint $table) {
            $table->index(['npc_id', 'mdt_id', 'kill_zone_id'], 'kill_zone_enemies_enemy_composite_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kill_zone_enemies', function (Blueprint $table) {
            $table->dropIndex('kill_zone_enemies_enemy_composite_index');
        });
    }
};
