<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameColumnsOnGameServerRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_server_regions', function (Blueprint $table) {
            $table->integer('reset_hours_offset')->after('reset_day_offset');
        });

        foreach (\App\Models\GameServerRegion::all() as $region) {
            // This field exists at this time
            $hour = (explode(':', $region->reset_time_offset_utc))[0];
            $region->reset_hours_offset = $hour;
            $region->save();
        }

        Schema::table('game_server_regions', function (Blueprint $table) {
            $table->dropColumn('reset_time_offset_utc');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Nah :p
    }
}
