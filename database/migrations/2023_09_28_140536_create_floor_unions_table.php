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
        Schema::create('floor_unions', function (Blueprint $table) {
            $table->id();
            $table->integer('floor_id');
            $table->integer('target_floor_id');
            $table->float('lat');
            $table->float('lng');
            $table->float('size');
            $table->float('rotation');

            $table->index(['floor_id', 'target_floor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('floor_unions');
    }
};
