<?php

namespace App\Service\Season;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface SeasonServiceInterface
{
    /**
     * @return Collection<int, Season>
     */
    public function getSeasons(?Expansion $expansion = null, ?GameServerRegion $region = null): Collection;

    /**
     * @return Collection<int, Season>
     */
    public function getAllSeasons(): Collection;

    public function getFirstSeason(): Season;

    public function getSeasonAt(Carbon $date, ?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season;

    public function getNextSeason(Season $season, ?GameServerRegion $region = null): ?Season;

    public function getCurrentSeason(?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season;

    public function getNextSeasonOfExpansion(?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season;

    public function getMostRecentSeasonForDungeon(Dungeon $dungeon): ?Season;

    public function getUpcomingSeasonForDungeon(Dungeon $dungeon): ?Season;

    public function getSeasonFromShortString(string $season): ?Season;

    /**
     * Find the season active at a given date across all expansions, skipping seasons with no affix groups defined.
     * Unlike getSeasonAt(), this is not scoped to a single expansion and filters out placeholder seasons.
     */
    public function findSeasonWithAffixGroupsAt(Carbon $date, GameServerRegion $region): ?Season;
}
