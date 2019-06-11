<?php

use Illuminate\Database\Migrations\Migration;

class ChangeAmericasRegionShort extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $region = \App\Models\GameServerRegion::where('short', 'na')->first();
        if ($region !== null) {
            $region->short = 'us';
            $region->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $region = \App\Models\GameServerRegion::where('short', 'us')->first();
        if ($region !== null) {
            $region->short = 'na';
            $region->save();
        }
    }
}
