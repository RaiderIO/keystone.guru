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
        $this->rollback();

        foreach (RaidMarker::ALL as $raidMarkerName => $id) {
            RaidMarker::create([
                'id'   => $id,
                'name' => $raidMarkerName,
            ]);
        }
    }

    private function rollback()
    {
        DB::table('raid_markers')->truncate();
    }
}
