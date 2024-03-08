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
        Schema::create('floor_union_areas', function (Blueprint $table) {
            $table->id();
            $table->integer('floor_union_id');
            $table->string('vertices_json');

            $table->index('floor_union_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('floor_union_areas');
    }
};
