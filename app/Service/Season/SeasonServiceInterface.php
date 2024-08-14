<?php

namespace App\Service\Season;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\Dtos\WeeklyAffixGroup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface SeasonServiceInterface
{
    public function getSeasons(?Expansion $expansion = null, ?GameServerRegion $region = null): Collection;

    public function getFirstSeason(): Season;

    public function getSeasonAt(Carbon $date, ?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season;

    public function getCurrentSeason(?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season;

    public function getNextSeasonOfExpansion(?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season;

    public function getMostRecentSeasonForDungeon(Dungeon $dungeon): ?Season;

    public function getAffixGroupIndexAt(Carbon $date, GameServerRegion $region, ?Expansion $expansion = null): ?int;

    public function getDisplayedAffixGroups(int $iterationOffset): Collection;

    /**
     * @return Collection<WeeklyAffixGroup>
     */
    public function getWeeklyAffixGroupsSinceStart(Season $season, GameServerRegion $region): Collection;
}
