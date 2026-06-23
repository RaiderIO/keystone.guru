<?php

namespace App\Service\Season;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\Dtos\WeeklyAffixGroup;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface SeasonAffixGroupServiceInterface
{
    /**
     * @throws Exception
     */
    public function getAffixGroupAt(Season $season, Carbon $date, ?GameServerRegion $region = null): ?AffixGroup;

    /**
     * @throws Exception
     */
    public function getAffixGroupIndexAt(Carbon $date, ?GameServerRegion $region = null, ?Expansion $expansion = null): ?int;

    public function getWeeksSinceStartAt(Season $season, Carbon $date): int;

    public function getAffixGroupIterations(Season $season): int;

    public function getAffixGroupIterationsAt(Season $season, Carbon $date): int;

    /**
     * @throws Exception
     */
    public function getCurrentAffixGroupInRegion(Season $season, GameServerRegion $region): ?AffixGroup;

    /**
     * @throws Exception
     */
    public function getNextAffixGroupInRegion(Season $season, ?GameServerRegion $region = null): ?AffixGroup;

    /**
     * @throws Exception
     */
    public function getCurrentAffixGroup(Season $season): ?AffixGroup;

    /**
     * @throws Exception
     */
    public function getNextAffixGroup(Season $season): ?AffixGroup;

    /**
     * @throws Exception
     */
    public function getPresetForAffixGroup(Season $season, AffixGroup $affixGroup): int;

    public function getPresetAtDate(Season $season, Carbon $date): int;

    /**
     * @return Collection<int, Affix>
     */
    public function getFeaturedAffixes(Season $season): Collection;

    /**
     * @return Collection<int, AffixGroup>
     * @throws Exception
     */
    public function getDisplayedAffixGroups(int $iterationOffset): Collection;

    /**
     * @return Collection<int, WeeklyAffixGroup>
     */
    public function getWeeklyAffixGroupsSinceStart(Season $season, GameServerRegion $region): Collection;
}
