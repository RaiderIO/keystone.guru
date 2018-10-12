<?php

use Illuminate\Database\Migrations\Migration;

class RemoveDuplicateKillzones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function up()
    {
        //
        $dungeonroutes = \App\Models\DungeonRoute::all();
        foreach ($dungeonroutes as $dungeonroute) {
            $previousKillZone = $dungeonroute->killzones->first();
            /** @var $dungeonroute \App\Models\DungeonRoute */
            for ($i = 1; $i < $dungeonroute->killzones->count(); $i++) {
                /** @var \App\Models\KillZone $killzone */
                $killzone = $dungeonroute->killzones->get($i);
                // If they're equal..
                if ($this->_compareKillzone($previousKillZone, $killzone)) {
                    // Delete the 2nd killzone
                    $killzone->delete();
                }

                $previousKillZone = $killzone;
            }
        }
    }

    /**
     * @param $killzone \App\Models\KillZone
     * @param $killzoneB \App\Models\KillZone
     * @return boolean
     */
    private function _compareKillzone($killzone, $killzoneB)
    {
        return $killzone->lat == $killzoneB->lat && $killzone->lng == $killzoneB->lng;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
