<?php

namespace Database\Seeders;

use App\Models\GameServerRegion;
use Illuminate\Database\Seeder;

class GameServerRegionsSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gameServerRegionAttributes = [
            // https://us.battle.net/forums/en/wow/topic/20745655899?page=1#post-1
            [
                'short'              => GameServerRegion::AMERICAS,
                'name'               => 'gameserverregions.us',
                'timezone'           => 'America/Los_Angeles',
                'reset_day_offset'   => 1,
                'reset_hours_offset' => 15,
            ],
            // https://wowreset.com/
            [
                'short'              => GameServerRegion::EUROPE,
                'name'               => 'gameserverregions.eu',
                'timezone'           => 'Europe/London',
                'reset_day_offset'   => 2,
                'reset_hours_offset' => 7,
            ],
            // Copy paste from America, I couldn't find info for these regions
            [
                'short'              => GameServerRegion::CHINA,
                'name'               => 'gameserverregions.cn',
                'timezone'           => 'Asia/Shanghai',
                'reset_day_offset'   => 1,
                'reset_hours_offset' => 15,
            ],
            [
                'short'              => GameServerRegion::TAIWAN,
                'name'               => 'gameserverregions.tw',
                'timezone'           => 'Asia/Taipei',
                'reset_day_offset'   => 1,
                'reset_hours_offset' => 15,
            ],
            // https://www.reddit.com/r/wow/comments/9sbujc/korean_wow_user_back_brought_some_korean_wow/e8ntkck/?context=3
            [
                'short'              => GameServerRegion::KOREA,
                'name'               => 'gameserverregions.kr',
                'timezone'           => 'Asia/Seoul',
                'reset_day_offset'   => 2,
                'reset_hours_offset' => 23,
            ],
        ];

        GameServerRegion::from(DatabaseSeeder::getTempTableName(GameServerRegion::class))->insert($gameServerRegionAttributes);
    }

    /**
     * @return string[]
     */
    public static function getAffectedModelClasses(): array
    {
        return [GameServerRegion::class];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
