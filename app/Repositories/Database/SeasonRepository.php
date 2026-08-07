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
         * AND seasons . start <= '2023-08-28 14:00:00'
         * ORDER BY seasons . start DESC
         * LIMIT 1
         */

        /** @var Season|null $season */
        $season = Season::selectRaw('seasons.*')
            ->join('season_dungeons', 'seasons.id', 'season_dungeons.season_id')
            ->where('season_dungeons.dungeon_id', $dungeon->id)
            ->where('seasons.start', '<=', now())
            ->orderBy('seasons.start', 'desc')
            ->first();

        return $season;
    }

    public function getUpcomingSeasonForDungeon(Dungeon $dungeon): ?Season
    {
        /**
         * SELECT seasons .*
         * FROM seasons
         * INNER JOIN season_dungeons ON seasons . id = season_dungeons . season_id
         * WHERE season_dungeons . dungeon_id = 77
         * AND seasons . start > '2023-08-28 14:00:00'
         * ORDER BY seasons . start DESC
         * LIMIT 1
         */

        /** @var Season|null $season */
        $season = Season::selectRaw('seasons.*')
            ->join('season_dungeons', 'seasons.id', 'season_dungeons.season_id')
            ->where('season_dungeons.dungeon_id', $dungeon->id)
            ->where('seasons.start', '>', now())
            // A deliberately far-future placeholder start date exists in the data (Season::SEASON_LEGION_TW_S1,
            // seeded for 2050) to keep it out of "upcoming" resolution for its dungeons - so this cannot be
            // dropped outright. Widened well past a year (#3868: King's Rest's real Season 2 assignment was
            // being lost by the old 1-year cap) while staying nowhere near that placeholder.
            ->where('seasons.start', '<', now()->addYears(3))
            ->orderBy('seasons.start', 'desc')
            ->first();

        return $season;
    }
}
