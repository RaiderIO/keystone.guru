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
        Schema::create('dungeon_route_collection_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 64)->unique('drc_categories_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dungeon_route_collection_categories');
    }
};
