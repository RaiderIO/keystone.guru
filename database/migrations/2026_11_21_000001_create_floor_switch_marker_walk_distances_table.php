<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('floor_switch_marker_walk_distances', function (Blueprint $table) {
            $table->id();
            $table->integer('mapping_version_id');
            $table->integer('floor_id');
            $table->integer('dungeon_floor_switch_marker_id');
            $table->integer('other_dungeon_floor_switch_marker_id');
            $table->float('straight_distance');
            $table->float('walk_distance')->nullable();
            $table->timestamps();

            $table->unique(['mapping_version_id', 'dungeon_floor_switch_marker_id', 'other_dungeon_floor_switch_marker_id'], 'fsmwd_mv_marker_other_unique');
            $table->index(['mapping_version_id', 'floor_id'], 'fsmwd_mv_floor_index');
            $table->index(['mapping_version_id', 'other_dungeon_floor_switch_marker_id'], 'fsmwd_mv_other_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floor_switch_marker_walk_distances');
    }
};
