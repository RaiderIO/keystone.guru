<?php

namespace App\Service\Season;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\Dtos\WeeklyAffixGroup;
use App\Service\TimewalkingEvent\TimewalkingEventServiceInterface;
use App\Traits\UserCurrentTime;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Handles all affix group calculations for seasons: which affix is active at a given time,
 * iteration counts, presets, featured affixes, and affix group display logic.
 *
 * @author Wouter
 *
 * @since 28/05/2026
 */
class SeasonAffixGroupService implements SeasonAffixGroupServiceInterface
{
    use UserCurrentTime;

    public function __construct(
        private readonly SeasonServiceInterface           $seasonService,
        private readonly TimewalkingEventServiceInterface $timewalkingEventService,
    ) {
    }

    /**
     * Get which affix group is active on this region at a specific point in time.
     *
     * @param  Season          $season The season for which you want to know the affix group.
     * @param  Carbon          $date   The date at which you want to know the affix group.
     * @return AffixGroup|null The affix group that is active at that point in time for your passed timezone.
     *
     * @throws Exception
     */
    public function getAffixGroupAt(Season $season, Carbon $date, ?GameServerRegion $region = null): ?AffixGroup
    {
        if ($season->expansion->hasTimewalkingEvent()) {
            return $this->timewalkingEventService->getAffixGroupAt($season->expansion, $date);
        }

        $affixGroupIndex = $this->getAffixGroupIndexAt($date, $region, $season->expansion);

        if ($affixGroupIndex === null) {
            return null;
        }

        return $affixGroupIndex < $season->affixGroups->count() ? $season->affixGroups[$affixGroupIndex] : null;
    }

    /**
     * @throws Exception
     */
    public function getAffixGroupIndexAt(
        Carbon            $date,
        ?GameServerRegion $region = null,
        ?Expansion        $expansion = null,
    ): ?int {
        $season = $this->seasonService->getSeasonAt($date, $expansion, $region);

        if ($season === null || $season->affix_group_count <= 0) {
            return null;
        }

        $seasonStart = $season->start($region);

        if ($seasonStart->gt($date)) {
            throw new Exception('Season at calculation is wrong; cannot find the affix group at a specific time
            because the season start date is past the target date!');
        }

        $elapsedWeeks = (int)$seasonStart->diffInWeeks($date, true);

        return ($season->start_affix_group_index + $elapsedWeeks) % $season->affix_group_count;
    }

    /**
     * Get the amount of weeks that have passed since the start of the M+ season, on a specific date.
     *
     * @param  Season $season
     * @param  Carbon $date
     * @return int
     */
    public function getWeeksSinceStartAt(Season $season, Carbon $date): int
    {
        $start = $season->start();

        $targetTime = Carbon::create($date->year, $date->month, $date->day, $date->hour, null, null, $date->timezone);

        return (int)$start->diffInWeeks($targetTime, true);
    }

    /**
     * Get the amount of full iterations of the entire list of affix groups that this season has done, since the start
     * of the season.
     *
     * @param  Season $season
     * @return int
     */
    public function getAffixGroupIterations(Season $season): int
    {
        return $this->getAffixGroupIterationsAt($season, Carbon::now());
    }

    /**
     * Get the amount of full iterations of the entire list of affix groups
     *
     * @param  Season $season
     * @param  Carbon $date
     * @return int
     */
    public function getAffixGroupIterationsAt(Season $season, Carbon $date): int
    {
        $weeksSinceStart = $this->getWeeksSinceStartAt($season, $date);

        return (int)($weeksSinceStart / $season->affixGroups->count());
    }

    /**
     * Get the affix group that is currently active in the region's timezone.
     *
     * @throws Exception
     */
    public function getCurrentAffixGroupInRegion(Season $season, GameServerRegion $region): ?AffixGroup
    {
        try {
            return $this->getAffixGroupAt($season, Carbon::now(), $region);
        } catch (Exception $exception) {
            Log::error('Error getting current affix group', [
                'exception' => $exception,
                'region'    => $region->short,
            ]);

            throw $exception;
        }
    }

    /**
     * Get the affix group that will be active next week in the region's timezone.
     *
     * @throws Exception
     */
    public function getNextAffixGroupInRegion(Season $season, ?GameServerRegion $region = null): ?AffixGroup
    {
        $region ??= GameServerRegion::getUserOrDefaultRegion();

        try {
            return $this->getAffixGroupAt($season, Carbon::now()->addWeek(), $region);
        } catch (Exception $exception) {
            Log::error('Error getting next affix group in region', [
                'exception' => $exception,
                'region'    => $region->short,
            ]);

            throw $exception;
        }
    }

    /**
     * Get the affix group that is currently active in the user's timezone (if user timezone was set).
     *
     * @throws Exception
     */
    public function getCurrentAffixGroup(Season $season): ?AffixGroup
    {
        $region = GameServerRegion::getUserOrDefaultRegion();

        try {
            return $this->getAffixGroupAt($season, Carbon::now(), $region);
        } catch (Exception $exception) {
            Log::error('Error getting current affix group', [
                'exception' => $exception,
                'region'    => $region->short,
            ]);

            throw $exception;
        }
    }

    /**
     * Get the affix group that will be active in the user's timezone next week (if user timezone was set).
     *
     * @throws Exception
     */
    public function getNextAffixGroup(Season $season): ?AffixGroup
    {
        $region = GameServerRegion::getUserOrDefaultRegion();

        try {
            return $this->getAffixGroupAt($season, Carbon::now()->addDays(7), $region);
        } catch (Exception $exception) {
            Log::error('Error getting next affix group', [
                'exception' => $exception,
                'region'    => $region->short,
            ]);

            throw new Exception('Error getting next affix group');
        }
    }

    /**
     * @throws Exception
     */
    public function getPresetForAffixGroup(Season $season, AffixGroup $affixGroup): int
    {
        $region     = GameServerRegion::getUserOrDefaultRegion();
        $startAffix = $this->getAffixGroupAt($season, $season->start($region), $region);

        $startIndex = $season->affixGroups->search(
            static fn(AffixGroup $g) => $startAffix !== null && $g->id === $startAffix->id,
        );
        $affixGroupIndex = $season->affixGroups->search($season->affixGroups->filter(static fn(
            AffixGroup $affixGroupCandidate,
        ) => $affixGroupCandidate->id === $affixGroup->id)->first());

        return $season->presets !== 0 ? ($startIndex + $affixGroupIndex % $season->affixGroups->count()) % $season->presets + 1 : 0;
    }

    /**
     * Get the current preset (if any) at a specific date.
     *
     * @return int The preset at the passed date.
     */
    public function getPresetAtDate(Season $season, Carbon $date): int
    {
        return $season->presets !== 0 ? $this->getWeeksSinceStartAt($season, $date) % $season->presets : 0;
    }

    /**
     * Get a list of unique affixes found in this season.
     *
     * @return Collection<int, Affix>
     */
    public function getFeaturedAffixes(Season $season): Collection
    {
        return Affix::query()
            ->selectRaw('affixes.*')
            ->join('affix_group_couplings', 'affix_group_couplings.affix_id', '=', 'affixes.id')
            ->join('affix_groups', 'affix_groups.id', '=', 'affix_group_couplings.affix_group_id')
            ->where('affix_groups.season_id', $season->id)
            ->get()
            ->unique('id');
    }

    /**
     * Get the affix groups to display on the affix overview page for the given iteration offset.
     *
     * The list is anchored to a full affix iteration of the current season (all `affix_group_count` weeks),
     * plus one spillover week before and one after, so the previous and next week are always visible without
     * paging. As weeks pass, the current week walks through the iteration and resets when the next iteration
     * begins. Each row shows the authoritative affix for its date, so it matches the page's "current affix".
     *
     * @return Collection<int, array{date_start: Carbon, affix_group: AffixGroup}>
     * @throws Exception
     */
    public function getDisplayedAffixGroups(int $iterationOffset): Collection
    {
        $region = GameServerRegion::getUserOrDefaultRegion();

        $affixGroups = new Collection();

        $now           = Carbon::now();
        $currentSeason = $this->seasonService->findSeasonWithAffixGroupsAt($now, $region);

        if ($currentSeason === null || $currentSeason->affix_group_count <= 0) {
            return $affixGroups;
        }

        $count              = $currentSeason->affix_group_count;
        $currentSeasonStart = $currentSeason->start($region);

        $weeksSinceStart = (int)$currentSeasonStart->diffInWeeks($now, true);
        $iterationIndex  = intdiv($weeksSinceStart, $count) + $iterationOffset;
        $anchorWeek      = $iterationIndex * $count;

        // Show the full iteration, plus one week before and one week after for context.
        for ($week = $anchorWeek - 1; $week <= $anchorWeek + $count; $week++) {
            $date   = $currentSeasonStart->copy()->addWeeks($week);
            $season = $this->seasonService->findSeasonWithAffixGroupsAt($date, $region);

            if ($season === null || $season->affix_group_count <= 0) {
                continue;
            }

            $elapsedWeeks = (int)$season->start($region)->diffInWeeks($date, true);
            $index        = ($season->start_affix_group_index + $elapsedWeeks) % $season->affix_group_count;

            if ($index >= $season->affixGroups->count()) {
                continue;
            }

            $affixGroups->push([
                'date_start'  => $date,
                'affix_group' => $season->affixGroups[$index],
            ]);
        }

        return $affixGroups;
    }

    /**
     * @return Collection<int, WeeklyAffixGroup>
     * @throws Exception
     */
    public function getWeeklyAffixGroupsSinceStart(Season $season, GameServerRegion $region): Collection
    {
        $result = collect();

        $currentDate = $season->start($region)->copy();
        $now         = Carbon::now();

        $week = 1;
        while ($currentDate->lt($now)) {
            $affixGroup = $this->getAffixGroupAt($season, $currentDate, $region);

            if ($affixGroup !== null) {
                $result->push(
                    new WeeklyAffixGroup(
                        $affixGroup,
                        $week,
                        $currentDate->copy(),
                    ),
                );
            } else {
                break;
            }

            $currentDate->addWeek();
            $week++;
        }

        return $result;
    }
}
