<?php

namespace App\Repositories\Database;

use App\Models\Dungeon;
use App\Models\Season;
use App\Repositories\Interfaces\SeasonRepositoryInterface;

class SeasonRepository extends DatabaseRepository implements SeasonRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Season::class);
    }

    public function getMostRecentSeasonForDungeon(Dungeon $dungeon): ?Season
    {
        /**
         * SELECT seasons .*
         * FROM seasons
         * INNER JOIN season_dungeons ON seasons . id = season_dungeons . season_id
         * WHERE season_dungeons . dungeon_id = 77
         * ORDER BY seasons . start DESC
         * LIMIT 1
         */

        /** @var Season|null $season */
        $season = Season::selectRaw('seasons.*')
            ->join('season_dungeons', 'seasons.id', 'season_dungeons.season_id')
            ->where('season_dungeons.dungeon_id', $dungeon->id)
            ->orderBy('seasons.start', 'desc')
            ->first();

        return $season;
    }
}
