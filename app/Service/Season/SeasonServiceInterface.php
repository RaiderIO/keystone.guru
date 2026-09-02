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

    /**
     * Every week of the given season that has already started, as `week number => keystone leaderboard period`.
     * Week 1 is the week the season starts in; the list stops at the start of the next season - of any expansion,
     * seasons run back to back across them - or at the current week, whichever comes first.
     *
     * @return Collection<int, int>
     */
    public function getWeeklyPeriods(Season $season, GameServerRegion $region): Collection;

    public function getCurrentSeason(?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season;

    public function getNextSeasonOfExpansion(?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season;

    public function getMostRecentSeasonForDungeon(Dungeon $dungeon): ?Season;

    public function getUpcomingSeasonForDungeon(Dungeon $dungeon): ?Season;

    /**
     * The current season, but only if the given dungeon is part of it. Returns null for dungeons
     * that are not in the currently active season (such as legacy dungeons).
     */
    public function getCurrentSeasonForDungeon(Dungeon $dungeon): ?Season;

    public function getSeasonFromShortString(string $season): ?Season;

    /**
     * Find the season active at a given date across all expansions, skipping seasons with no affix groups defined.
     * Unlike getSeasonAt(), this is not scoped to a single expansion and filters out placeholder seasons.
     */
    public function findSeasonWithAffixGroupsAt(Carbon $date, GameServerRegion $region): ?Season;
}
