<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('dungeon_route_thumbnail_jobs', function (Blueprint $table) {
            $table->float('zoom_level')->after('height')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('dungeon_route_thumbnail_jobs', function (Blueprint $table) {
            $table->dropColumn('zoom_level');
        });
    }
};
