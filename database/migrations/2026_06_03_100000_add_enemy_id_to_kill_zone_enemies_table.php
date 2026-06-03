<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('kill_zone_enemies', 'enemy_id')) {
            Schema::table('kill_zone_enemies', function (Blueprint $table) {
                $table->unsignedInteger('enemy_id')->nullable()->after('mdt_id');
            });
        }

        if (!Schema::hasIndex('kill_zone_enemies', 'kill_zone_enemies_enemy_id_index')) {
            Schema::table('kill_zone_enemies', function (Blueprint $table) {
                $table->index('enemy_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('kill_zone_enemies', function (Blueprint $table) {
            $table->dropIndex(['enemy_id']);
            $table->dropColumn('enemy_id');
        });
    }
};
