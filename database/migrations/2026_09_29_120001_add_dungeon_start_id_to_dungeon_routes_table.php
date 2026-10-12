<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->unsignedInteger('dungeon_start_id')->nullable()->after('dungeon_start_map_icon_id');
            $table->index('dungeon_start_id');
        });
    }

    public function down(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropIndex(['dungeon_start_id']);
            $table->dropColumn('dungeon_start_id');
        });
    }
};
