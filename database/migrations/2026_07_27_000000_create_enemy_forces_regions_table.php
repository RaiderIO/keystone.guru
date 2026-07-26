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
        Schema::create('enemy_forces_regions', function (Blueprint $table) {
            $table->id();
            $table->integer('mapping_version_id')->default(0);
            // The floor the region's pill is anchored on. The region's enemies may live on other
            // floors as well - a corridor is not bound to a single floor.
            $table->integer('floor_id');
            $table->string('name');
            $table->double('lat');
            $table->double('lng');

            $table->index(['floor_id', 'mapping_version_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enemy_forces_regions');
    }
};
