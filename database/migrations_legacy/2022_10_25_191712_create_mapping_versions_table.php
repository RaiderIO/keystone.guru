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
        Schema::create('mapping_versions', function (Blueprint $table) {
            $table->id();
            $table->integer('dungeon_id');
            $table->integer('version');

            $table->timestamps();

            $table->index(['dungeon_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mapping_versions');
    }
};
