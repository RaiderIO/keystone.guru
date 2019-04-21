<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
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
        $region->short = 'us';
        $region->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $region = \App\Models\GameServerRegion::where('short', 'us')->first();
        $region->short = 'na';
        $region->save();
    }
}
