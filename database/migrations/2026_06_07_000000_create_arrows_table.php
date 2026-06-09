<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('arrows', static function (Blueprint $table) {
            $table->id();
            $table->integer('dungeon_route_id');
            $table->integer('floor_id');
            $table->integer('polyline_id')->nullable();
            $table->timestamps();

            $table->index(['dungeon_route_id', 'floor_id']);
            $table->index('floor_id');
            $table->index('polyline_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrows');
    }
};
