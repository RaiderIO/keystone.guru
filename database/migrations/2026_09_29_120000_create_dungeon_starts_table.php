<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dungeon_starts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mapping_version_id');
            $table->integer('floor_id');
            $table->integer('target_dungeon_id')->nullable();
            $table->double('lat');
            $table->double('lng');
            $table->text('comment')->nullable();

            $table->index(['mapping_version_id', 'floor_id']);
            $table->index('floor_id');
            $table->index('target_dungeon_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dungeon_starts');
    }
};
