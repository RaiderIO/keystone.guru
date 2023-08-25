<?php

namespace Database\Seeders;

use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameVersionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->rollback();
        $this->command->info('Adding known game versions');

        $gameVersionAttributes = [];

        foreach (GameVersion::ALL as $key => $id) {
            $gameVersionAttributes[] = [
                'id'          => $id,
                'key'         => $key,
                'name'        => sprintf('gameversions.%s.name', $key),
                'description' => sprintf('gameversions.%s.description', $key),
                'has_seasons' => $key === GameVersion::GAME_VERSION_RETAIL,
            ];
        }

        GameVersion::insert($gameVersionAttributes);
    }

    private function rollback()
    {
        DB::table('game_versions')->truncate();
    }
}
