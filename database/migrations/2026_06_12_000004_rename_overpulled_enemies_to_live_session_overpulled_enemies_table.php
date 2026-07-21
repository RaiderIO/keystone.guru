<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Originally a Schema::rename() of the pre-existing `overpulled_enemies` table. Deploys here
     * are not atomic (the migrate cron runs independent of the web rollout), and master's
     * OverpulledEnemyService/AjaxOverpulledEnemyController still query `overpulled_enemies` by
     * name - a still-running previous-release container would 500 the moment this rename lands.
     * Creating a brand new table instead (matching the intended final shape - no `enemy_id`, see
     * the now-removed follow-up drop migration folded in here) is purely additive and safe. The
     * old `overpulled_enemies` table is intentionally left behind for a later release to drop,
     * once nothing on the previous release still queries it.
     */
    public function up(): void
    {
        Schema::create('live_session_overpulled_enemies', static function (Blueprint $table) {
            $table->id();
            $table->integer('live_session_id');
            $table->integer('kill_zone_id');
            $table->integer('npc_id')->nullable();
            $table->integer('mdt_id')->nullable();

            $table->index('live_session_id');
            $table->index('kill_zone_id');
            $table->index(['npc_id', 'mdt_id']);
            $table->index(['live_session_id', 'kill_zone_id'], 'lsopmies_live_session_id_kill_zone_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_overpulled_enemies');
    }
};
