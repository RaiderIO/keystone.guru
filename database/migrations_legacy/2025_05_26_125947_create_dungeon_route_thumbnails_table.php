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
        Schema::create('dungeon_route_thumbnails', function (Blueprint $table) {
            $table->id();
            $table->integer('dungeon_route_id')->index();
            $table->integer('floor_id')->index();
            $table->integer('file_id')->index();
            $table->boolean('custom')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dungeon_route_thumbnails');
    }
};
