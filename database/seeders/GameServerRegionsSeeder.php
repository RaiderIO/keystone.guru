<?php

namespace Database\Seeders;

use App\Models\GameServerRegion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameServerRegionsSeeder extends Seeder
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
            // https://us.battle.net/forums/en/wow/topic/20745655899?page=1#post-1
            new GameServerRegion([
                'short'              => GameServerRegion::AMERICAS,
                'name'               => 'gameserverregions.us',
                'timezone'           => 'America/Los_Angeles',
                'reset_day_offset'   => 2,
                'reset_hours_offset' => 15,
            ]),
            // http://wowreset.com/
            new GameServerRegion([
                'short'              => GameServerRegion::EUROPE,
                'name'               => 'gameserverregions.eu',
                'timezone'           => 'Europe/London',
                'reset_day_offset'   => 3,
                'reset_hours_offset' => 7,
            ]),
            // Copy paste from America, I couldn't find info for these regions
            new GameServerRegion([
                'short'              => GameServerRegion::CHINA,
                'name'               => 'gameserverregions.cn',
                'timezone'           => 'Asia/Shanghai',
                'reset_day_offset'   => 2,
                'reset_hours_offset' => 15,
            ]),
            new GameServerRegion([
                'short'              => GameServerRegion::TAIWAN,
                'name'               => 'gameserverregions.tw',
                'timezone'           => 'Asia/Taipei',
                'reset_day_offset'   => 2,
                'reset_hours_offset' => 15,
            ]),
            // https://www.reddit.com/r/wow/comments/9sbujc/korean_wow_user_back_brought_some_korean_wow/e8ntkck/?context=3
            new GameServerRegion([
                'short'              => GameServerRegion::KOREA,
                'name'               => 'gameserverregions.kr',
                'timezone'           => 'Asia/Seoul',
                'reset_day_offset'   => 3,
                'reset_hours_offset' => 23,
            ]),
        ];

        foreach ($gameServerRegions as $gameServerRegion) {
            $gameServerRegion->save();
        }
    }

    private function _rollback()
    {
        DB::table('game_server_regions')->truncate();
    }
}
