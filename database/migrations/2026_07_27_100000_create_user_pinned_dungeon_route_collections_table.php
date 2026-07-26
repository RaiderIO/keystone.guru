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
        Schema::create('user_pinned_dungeon_route_collections', function (Blueprint $table) {
            $table->id();
            // The generated index names would exceed MySQL's 64 character limit, hence the
            // explicit short names
            $table->unsignedInteger('user_id')->index('upc_user_id_index');
            $table->unsignedBigInteger('dungeon_route_collection_id')->index('upc_collection_id_index');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            // A collection may only be pinned once by the same user
            $table->unique(['user_id', 'dungeon_route_collection_id'], 'upc_user_collection_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_pinned_dungeon_route_collections');
    }
};
