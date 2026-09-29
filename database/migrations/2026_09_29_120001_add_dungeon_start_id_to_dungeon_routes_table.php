<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->unsignedInteger('dungeon_start_id')->nullable()->after('dungeon_start_map_icon_id');
            $table->index('dungeon_start_id');
        });

        // Has to run before the seeder: seeding deletes the map icons dungeon_start_map_icon_id points at
        Artisan::call('dungeonroute:backfilldungeonstartid');
    }

    public function down(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropIndex(['dungeon_start_id']);
            $table->dropColumn('dungeon_start_id');
        });
    }
};
