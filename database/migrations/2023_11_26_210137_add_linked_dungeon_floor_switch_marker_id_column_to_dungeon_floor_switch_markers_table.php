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
        Schema::table('dungeon_floor_switch_markers', function (Blueprint $table) {
            $table->integer('linked_dungeon_floor_switch_marker_id')->after('target_floor_id')->nullable();

            $table->index(['linked_dungeon_floor_switch_marker_id'], 'linked_floor_switch_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_floor_switch_markers', function (Blueprint $table) {
            $table->dropColumn('linked_dungeon_floor_switch_marker_id');
        });
    }
};
