<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('enemy_pack_walk_distances', function (Blueprint $table) {
            $table->id();
            $table->integer('mapping_version_id');
            $table->integer('floor_id');
            $table->integer('enemy_pack_id');
            $table->integer('other_enemy_pack_id');
            $table->float('straight_distance');
            $table->float('walk_distance')->nullable();
            $table->timestamps();

            $table->unique(['mapping_version_id', 'enemy_pack_id', 'other_enemy_pack_id'], 'epwd_mv_pack_other_unique');
            $table->index(['mapping_version_id', 'floor_id'], 'epwd_mv_floor_index');
            $table->index(['mapping_version_id', 'other_enemy_pack_id'], 'epwd_mv_other_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enemy_pack_walk_distances');
    }
};
