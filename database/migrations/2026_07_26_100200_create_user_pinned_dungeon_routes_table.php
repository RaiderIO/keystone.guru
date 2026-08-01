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
        Schema::create('user_pinned_dungeon_routes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->index();
            $table->unsignedInteger('dungeon_route_id')->index();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            // A route may only be pinned once by the same user
            $table->unique(['user_id', 'dungeon_route_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_pinned_dungeon_routes');
    }
};
