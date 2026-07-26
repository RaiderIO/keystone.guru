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
        Schema::create('dungeon_route_collection_routes', function (Blueprint $table) {
            $table->id();
            // The generated index names would exceed MySQL's 64 character limit, hence the
            // explicit short names
            $table->unsignedBigInteger('dungeon_route_collection_id')->index('drc_routes_collection_id_index');
            $table->unsignedInteger('dungeon_route_id')->index('drc_routes_dungeon_route_id_index');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            // A route may only be in the same collection once
            $table->unique(['dungeon_route_collection_id', 'dungeon_route_id'], 'drc_routes_collection_route_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dungeon_route_collection_routes');
    }
};
