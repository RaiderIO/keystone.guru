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
        Schema::create('enemy_forces_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->integer('mapping_version_id')->default(0);
            // The floor the checkpoint's pill is anchored on. The checkpoint's enemies may live on other
            // floors as well - a corridor is not bound to a single floor.
            $table->integer('floor_id');
            // Optional: a freshly placed checkpoint is saved immediately, before the mapper has had a
            // chance to name it in the popup.
            $table->string('name')->nullable();
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
        Schema::dropIfExists('enemy_forces_checkpoints');
    }
};
