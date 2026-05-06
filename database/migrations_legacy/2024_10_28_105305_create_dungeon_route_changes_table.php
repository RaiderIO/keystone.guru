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
        Schema::create('dungeon_route_changes', function (Blueprint $table) {
            $table->id();
            $table->integer('dungeon_route_id');
            $table->integer('user_id')->nullable();
            $table->integer('team_id')->nullable();
            $table->integer('team_role')->nullable();
            $table->integer('model_id');
            $table->string('model_class');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();

            // Filter by class (pulls, icons etc.)
            $table->index(['dungeon_route_id', 'model_class']);
            // Who changed this route?
            $table->index(['dungeon_route_id', 'user_id']);
            // What routes did I change?
            $table->index(['user_id', 'dungeon_route_id']);
            // Find changes to a specific model
            $table->index(['model_id', 'model_class']);
            // What team was assigned to this route when the change took place
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dungeon_route_changes');
    }
};
