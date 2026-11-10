<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('open_world_npcs', function (Blueprint $table) {
            // The in-game NPC id, like npcs.id
            $table->unsignedInteger('id')->primary();
            $table->string('name');
            $table->unsignedInteger('display_id')->nullable();
            $table->boolean('has_portrait')->default(false);
            $table->timestamps();

            $table->index('display_id', 'open_world_npcs_display_id_index');
            $table->index('has_portrait', 'open_world_npcs_has_portrait_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_world_npcs');
    }
};
