<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('combat_log_route_enemy_failures', function (Blueprint $table) {
            $table->id();
            $table->integer('dungeon_id')->index();
            $table->integer('floor_id');
            $table->integer('mapping_version_id');
            $table->integer('npc_id')->nullable();
            $table->double('lat');
            $table->double('lng');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combat_log_route_enemy_failures');
    }
};
