<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MoveTolDagorEnemiesToDifferentFloor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // These IDs moved to a different floor altogether (because MDT). So we have to migrate all killzones that use
        // this pack (all of them) to the different floor to prevent breaking them all.
        $ids = [2247, 2248, 2249, 2250, 2251];

        // Select all killzones that need updating
        $killZones = DB::table('kill_zone_enemies')
            ->whereIn('enemy_id', $ids)
            ->groupBy('kill_zone_id')
            ->select('kill_zone_id')->get();

        foreach ($killZones as $killZoneData) {
            /** @var \App\Models\KillZone $killZone */
            $killZone = \App\Models\KillZone::find($killZoneData->kill_zone_id);
            // Should not happen but whatever
            if ($killZone !== null) {
                // Move it to the next floor and set a decent position for it
                $killZone->floor_id = 54;
                $killZone->lat = -128.76;
                $killZone->lng = 260;
                $killZone->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('different_floor', function (Blueprint $table) {
            //
        });
    }
}
