<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A new table rather than a Schema::rename() of `overpulled_enemies`: deploys are not atomic
     * (the migrate cron runs independent of the web rollout), and the previous release queries
     * `overpulled_enemies` by name, so a rename would 500 every still-running container the moment
     * it lands. The table is created in its final shape (no `enemy_id`), and `overpulled_enemies`
     * is left for a later release to drop, once nothing still queries it.
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
