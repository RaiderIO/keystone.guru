<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'combatlog';

    public function up(): void
    {
        Schema::connection('combatlog')->table('combat_log_route_enemy_failures', function (Blueprint $table) {
            $table->integer('dungeon_route_id')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::connection('combatlog')->table('combat_log_route_enemy_failures', function (Blueprint $table) {
            $table->dropIndex(['dungeon_route_id']);
            $table->dropColumn('dungeon_route_id');
        });
    }
};
