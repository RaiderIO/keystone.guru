<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('live_session_overpulled_enemies', static function (Blueprint $table) {
            // Drop indexes that reference enemy_id before dropping the column.
            // Indexes kept their original prefix from before the table rename.
            if (Schema::hasColumn('live_session_overpulled_enemies', 'enemy_id')) {
                $table->dropIndex('overpulled_enemies_enemy_id_index');
                $table->dropIndex('overpulled_enemies_live_session_id_kill_zone_id_enemy_id_index');
                $table->dropColumn('enemy_id');
            }

            $table->index(['live_session_id', 'kill_zone_id'], 'lsopmies_live_session_id_kill_zone_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('live_session_overpulled_enemies', static function (Blueprint $table) {
            $table->dropIndex('lsopmies_live_session_id_kill_zone_id_index');
            $table->integer('enemy_id')->default(0)->after('kill_zone_id');
            $table->index('enemy_id', 'overpulled_enemies_enemy_id_index');
            $table->index(['live_session_id', 'kill_zone_id', 'enemy_id'], 'overpulled_enemies_live_session_id_kill_zone_id_enemy_id_index');
        });
    }
};
