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
            $table->integer('file_id')->nullable()->after('floor_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_route_thumbnail_jobs', function (Blueprint $table) {
            $table->dropColumn('file_id');
        });
    }
};
