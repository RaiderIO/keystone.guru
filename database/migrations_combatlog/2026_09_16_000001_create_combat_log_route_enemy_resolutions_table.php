<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The table name leaves too little room for Laravel's generated index names within MySQL's 64 character
        // identifier limit, so every index here is named explicitly.
        Schema::create('combat_log_route_enemy_resolutions', function (Blueprint $table) {
            $table->id();
            $table->integer('dungeon_route_id')->nullable()->index('clrer_dungeon_route_id_index');
            $table->string('source', 32)->nullable();
            $table->integer('dungeon_id');
            $table->integer('floor_id');
            $table->integer('mapping_version_id');
            $table->integer('npc_id')->nullable();
            $table->integer('enemy_id');
            $table->double('lat');
            $table->double('lng');
            $table->double('enemy_lat');
            $table->double('enemy_lng');
            $table->double('distance');
            $table->double('weighted_distance');
            $table->timestamps();

            $table->index(['dungeon_id', 'mapping_version_id'], 'clrer_dungeon_mapping_version_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combat_log_route_enemy_resolutions');
    }
};
