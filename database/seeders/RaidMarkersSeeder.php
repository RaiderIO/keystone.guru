<?php

namespace Database\Seeders;

use App\Models\RaidMarker;
use Illuminate\Database\Seeder;

class RaidMarkersSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $raidMarkerAttributes = [];
        foreach (RaidMarker::ALL as $raidMarkerName => $id) {
            $raidMarkerAttributes[] = [
                'id'   => $id,
                'name' => $raidMarkerName,
            ];
        }

        RaidMarker::from(DatabaseSeeder::getTempTableName(RaidMarker::class))->insert($raidMarkerAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [RaidMarker::class];
    }
}
