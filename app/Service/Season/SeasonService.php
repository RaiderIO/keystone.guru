<?php

namespace App\Service\Season;

use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Expansion\ExpansionService;
use App\Traits\UserCurrentTime;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * This service provides functionality for reading the current laravel echo service and parsing its contents.
 *
 * @author Wouter
 *
 * @since 17/06/2019
 */
class SeasonService implements SeasonServiceInterface
{
    use UserCurrentTime;

    /** @var Collection|Season[] */
    private Collection $seasonCache;

    private ?Season $firstSeasonCache = null;

    public function __construct(
        private ExpansionService $expansionService
    ) {
        $this->seasonCache      = collect();
        $this->firstSeasonCache = null;
    }

    /**
     * @return Collection<Season>
     */
    public function getSeasons(?Expansion $expansion = null, ?GameServerRegion $region = null): Collection
    {
        $expansion ??= $this->expansionService->getCurrentExpansion(
            $region ?? GameServerRegion::getUserOrDefaultRegion()
        );

        if ($this->seasonCache->empty()) {
            $this->seasonCache = Season::selectRaw('seasons.*')
                ->leftJoin('timewalking_events', 'timewalking_events.expansion_id', 'seasons.expansion_id')
                ->whereNull('timewalking_events.id')
                ->orderBy('seasons.start')
                ->get();
        }

        return $this->seasonCache->when($expansion !== null, static fn(Collection $seasonCache) => $seasonCache->where('expansion_id', $expansion->id));
    }

    public function getFirstSeason(): Season
    {
        if ($this->firstSeasonCache === null) {
            $this->firstSeasonCache = Season::selectRaw('seasons.*')
                ->leftJoin('timewalking_events', 'timewalking_events.expansion_id', 'seasons.expansion_id')
                ->whereNull('timewalking_events.id')
                ->orderBy('seasons.start')
                ->limit(1)
                ->first();
        }

        return $this->firstSeasonCache;
    }

    public function getNextSeason(Season $season, ?GameServerRegion $region): ?Season
    {
        $seasons = $this->getSeasons($season->expansion, $region);

        foreach ($seasons as $seasonCandidate) {
            if ($seasonCandidate->start->isAfter($season->start)) {
                return $seasonCandidate;
            }
        }

        return null;
    }

    /**
     * Get the season that was active at a specific date.
     */
    public function getSeasonAt(Carbon $date, GameServerRegion $region, ?Expansion $expansion = null): ?Season
    {
        $expansion ??= $this->expansionService->getCurrentExpansion($region);

        /** @var Season $season */
        $season = Season::whereRaw('DATE_ADD(DATE_ADD(`start`, INTERVAL ? day), INTERVAL ? hour) < ?',
            [$region->reset_day_offset, $region->reset_hours_offset, $date]
        )
            ->where('expansion_id', $expansion->id)
            ->orderBy('start', 'desc')
            ->limit(1)
            ->first();

        return $season;
    }

    /**
     * @param Expansion|null $expansion The expansion you want the current season for - or null to get it for the current expansion.
     * @return Season|null The season that's currently active, or null if none is active at this time.
     */
    public function getCurrentSeason(?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season
    {
        $region    ??= GameServerRegion::getUserOrDefaultRegion();
        $expansion ??= $this->expansionService->getCurrentExpansion($region);

        return $this->getSeasonAt(Carbon::now(), $region, $expansion);
    }

    public function getNextSeasonOfExpansion(?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season
    {
        $region    ??= GameServerRegion::getUserOrDefaultRegion();
        $expansion ??= $this->expansionService->getCurrentExpansion($region);

        return $expansion->nextSeason($region);
    }

    /**
     * Get the index in the list of affix groups that we're currently at. Each season has 12 affix groups per iteration.
     * We can calculate where exactly we are in the current iteration, we just don't know the affix group that represents
     * that index, that's up to the current season.
     *
     * @throws Exception
     */
    public function getAffixGroupIndexAt(Carbon $date, GameServerRegion $region, ?Expansion $expansion = null): int
    {
        $season      = $this->getSeasonAt($date, $region, $expansion);
        $seasonStart = $season->start($region);

        if ($seasonStart->gt($date)) {
            throw new Exception('Season at calculation is wrong; cannot find the affix group at a specific time
            because the season start date is past the target date!');
        }

        $elapsedWeeks = $seasonStart->diffInWeeks($date);

        return ($season->start_affix_group_index + $elapsedWeeks) % $season->affix_group_count;
    }

    /**
     * Get the affix groups that should be displayed in a table in the /affixes page.
     *
     * @param  $iterationOffset  int An optional offset to display affixes in the past or future.
     *
     * @throws Exception
     *
     * @todo This can be further improved with some mathy things, but for now it's quick enough
     */
    public function getDisplayedAffixGroups(int $iterationOffset): Collection
    {
        $seasons = Season::selectRaw('seasons.*')
            ->leftJoin('timewalking_events', 'timewalking_events.expansion_id', 'seasons.expansion_id')
            ->whereNull('timewalking_events.id')
            ->orderBy('start')
            ->get();

        /** @var Season $currentSeason */
        $currentSeason = $seasons->shift();
        /** @var Season $nextSeason */
        $nextSeason = $seasons->shift();

        // Add two weeks so that we can show an additional affix before and after the list so that we can always see what's coming up next
        $affixesToDisplay = 10;

        $firstSeasonStart = $currentSeason->start();

        // Ensure that we cannot go beyond the start of the first season - there's nothing before that
        $beginDate           = Carbon::now()->addWeeks($iterationOffset * $affixesToDisplay)->maximum($firstSeasonStart);
        $weeksSinceBeginning = $currentSeason->getWeeksSinceStartAt($beginDate);

        /** @var CacheServiceInterface $cacheService */
        //        $cacheService = App::make(CacheServiceInterface::class);
        //        return $cacheService->remember(sprintf('displayed_affix_groups_%d', $iterationOffset), function () use ($iterationOffset)
        //        {
        // Gotta start at the beginning to work out what we should display

        // We're going to solve this by starting at the beginning, and then simulating all the M+ weeks so far.
        // Since seasons may start/end at any time during the iteration of affix groups, we need to start at the
        // beginning and add affixes. Once we've simulated everything in the past up until and including the current
        // iteration, we can take off 12 affix groups and return those as those are the affixes we should display!
        $affixGroups          = new Collection();
        $simulatedTime        = $firstSeasonStart->copy();
        $totalWeeksToSimulate = $weeksSinceBeginning + 1;
        for ($i = 0; $i < $totalWeeksToSimulate; $i++) {
            if ($nextSeason !== null && $nextSeason->affixGroups->isNotEmpty()) {
                // If we should switch to the next season...
                if ($simulatedTime->gte($nextSeason->start())) {
                    // Move to the next season
                    $currentSeason = $nextSeason;
                    $nextSeason    = $seasons->shift();
                }
            }

            // Keep this affix group (or not)
            if (($totalWeeksToSimulate - $i) <= $affixesToDisplay) {
                // Append to the list of when we have which affix groups
                $affixGroups->push([
                    'date_start' => $simulatedTime->copy(),
                    'affixgroup' => $currentSeason->affixGroups[$i % $currentSeason->affix_group_count],
                ]);
            }

            // Add another week and continue..
            $simulatedTime->addWeek();
        }

        return $affixGroups;
        //        }, config('keystoneguru.cache.displayed_affix_groups.ttl'));
    }
}
