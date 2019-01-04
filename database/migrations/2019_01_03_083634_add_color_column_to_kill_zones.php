<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColorColumnToKillZones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kill_zones', function (Blueprint $table) {
            $table->string('color')->after('floor_id');
        });
        // Because of changes to the kill zones and the preview page, reset all thumbnails
        DB::table('dungeon_routes')->update(['thumbnail_updated_at' => '1980-01-01 00:00:00']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kill_zones', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
}
