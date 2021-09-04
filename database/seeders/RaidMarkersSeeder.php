<?php

namespace Database\Seeders;

use App\Models\RaidMarker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RaidMarkersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();

        $raidMarkerData = [
            'star',
            'circle',
            'diamond',
            'triangle',
            'moon',
            'square',
            'cross',
            'skull',
        ];

        foreach ($raidMarkerData as $raidMarkerObj) {
            $raidMarker       = new RaidMarker();
            $raidMarker->name = $raidMarkerObj;
            $raidMarker->save();
        }
    }

    private function _rollback()
    {
        DB::table('raid_markers')->truncate();
    }
}
