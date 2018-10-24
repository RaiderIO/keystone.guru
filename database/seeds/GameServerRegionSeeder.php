<?php

use Illuminate\Database\Seeder;
use App\Models\GameServerRegion;

class GameServerRegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();
        $this->command->info('Adding known game server regions');

        $gameServerRegions = [
            new GameServerRegion(['short' => 'na', 'name' => 'Americas']),
            new GameServerRegion(['short' => 'eu', 'name' => 'Europe']),
            new GameServerRegion(['short' => 'cn', 'name' => 'China']),
            new GameServerRegion(['short' => 'tw', 'name' => 'Taiwan']),
            new GameServerRegion(['short' => 'kr', 'name' => 'Korea'])
        ];

        foreach ($gameServerRegions as $gameServerRegion) {
            /** @var $gameServerRegion \Illuminate\Database\Eloquent\Model */
            $gameServerRegion->save();
        }
    }

    private function _rollback()
    {
        DB::table('game_server_regions')->truncate();
    }
}
