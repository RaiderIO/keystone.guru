<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('combat_log_route_enemy_failures', function (Blueprint $table) {
            $table->string('source', 32)->nullable()->after('dungeon_route_id');
        });
    }

    public function down(): void
    {
        Schema::table('combat_log_route_enemy_failures', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
