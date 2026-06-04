<?php

namespace App\Service\Season;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Non-working stub for SeasonAffixGroupServiceInterface — used where the real implementation is not needed.
 *
 * @author Wouter
 *
 * @since 28/05/2026
 */
class SeasonAffixGroupServiceStub implements SeasonAffixGroupServiceInterface
{
    public function getAffixGroupAt(Season $season, Carbon $date, ?GameServerRegion $region = null): ?AffixGroup
    {
        return null;
    }

    public function getAffixGroupIndexAt(Carbon $date, ?GameServerRegion $region = null, ?Expansion $expansion = null): ?int
    {
        return null;
    }

    public function getWeeksSinceStartAt(Season $season, Carbon $date): int
    {
        return 0;
    }

    public function getAffixGroupIterations(Season $season): int
    {
        return 0;
    }

    public function getAffixGroupIterationsAt(Season $season, Carbon $date): int
    {
        return 0;
    }

    public function getCurrentAffixGroupInRegion(Season $season, GameServerRegion $region): ?AffixGroup
    {
        return null;
    }

    public function getNextAffixGroupInRegion(Season $season, ?GameServerRegion $region = null): ?AffixGroup
    {
        return null;
    }

    public function getCurrentAffixGroup(Season $season): ?AffixGroup
    {
        return null;
    }

    public function getNextAffixGroup(Season $season): ?AffixGroup
    {
        return null;
    }

    public function getPresetForAffixGroup(Season $season, AffixGroup $affixGroup): int
    {
        return 0;
    }

    public function getPresetAtDate(Season $season, Carbon $date): int
    {
        return 0;
    }

    public function getFeaturedAffixes(Season $season): Collection
    {
        return collect();
    }

    public function getDisplayedAffixGroups(int $iterationOffset): Collection
    {
        return collect();
    }

    public function getWeeklyAffixGroupsSinceStart(Season $season, GameServerRegion $region): Collection
    {
        return collect();
    }
}
