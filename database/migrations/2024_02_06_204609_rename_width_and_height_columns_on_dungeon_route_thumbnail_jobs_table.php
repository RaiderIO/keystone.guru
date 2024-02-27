<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dungeon_route_thumbnail_jobs', function (Blueprint $table) {
            $table->renameColumn('width', 'image_width');
            $table->renameColumn('height', 'image_height');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_route_thumbnail_jobs', function (Blueprint $table) {
            $table->renameColumn('image_width', 'width');
            $table->renameColumn('image_height', 'height');
        });
    }
};
