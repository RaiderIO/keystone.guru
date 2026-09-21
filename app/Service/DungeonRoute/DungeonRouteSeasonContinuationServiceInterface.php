<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Season;
use App\Service\DungeonRoute\Exceptions\SeasonContinuationException;
use Illuminate\Support\Collection;

/**
 * A route's season is fixed once it is created. When its dungeon carries over into a newer season, the route is
 * continued there as a copy, leaving the original untouched in its own season.
 */
interface DungeonRouteSeasonContinuationServiceInterface
{
    /**
     * The newer season the route can be continued in, or null when its dungeon is in no newer season or its
     * author already continued it there.
     */
    public function getContinuationSeason(DungeonRoute $dungeonRoute): ?Season;

    /**
     * @param  Collection<int, DungeonRoute> $dungeonRoutes
     * @return Collection<int, Season>       Keyed by dungeon route ID; routes without a continuation season are absent
     */
    public function getContinuationSeasons(Collection $dungeonRoutes): Collection;

    /**
     * Copies the route into its continuation season: the copy moves to the dungeon's latest mapping version and
     * keeps only the affix groups of that season, the original is left as it is.
     *
     * @throws SeasonContinuationException
     */
    public function continueInNewerSeason(DungeonRoute $source): DungeonRoute;
}
