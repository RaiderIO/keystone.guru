<?php

namespace App\Service\Season;


use App\Models\Expansion;
use App\Models\Season;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Expansion\ExpansionService;
use App\Traits\UserCurrentTime;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

/**
 * This service provides functionality for reading the current laravel echo service and parsing its contents.
 * @package App\Service\Season
 * @author Wouter
 * @since 17/06/2019
 */
class SeasonService implements SeasonServiceInterface
{
    use UserCurrentTime;

    /** @var Collection|Season[] */
    private $seasonCache;

    /** @var ExpansionService */
    private $expansionService;

    /** @var Season */
    private $firstSeasonCache = null;

    public function __construct()
    {
        $this->expansionService = App::make(ExpansionService::class);
        $this->seasonCache      = collect();
        $this->firstSeasonCache = null;
    }

    /**
     * @param Expansion|null $expansion
     * @return Collection
     */
    public function getSeasons(?Expansion $expansion = null): Collection
    {
        $expansion = $expansion ?? $this->expansionService->getCurrentExpansion();

        if ($this->seasonCache->empty()) {
            $this->seasonCache = Season::selectRaw('seasons.*')
                ->leftJoin('timewalking_events', 'timewalking_events.expansion_id', 'seasons.expansion_id')
                ->whereNull('timewalking_events.id')
                ->orderBy('seasons.start')
                ->get();
        }

        return $this->seasonCache->when($expansion !== null, function (Collection $seasonCache) use ($expansion) {
            return $seasonCache->where('expansion_id', $expansion->id);
        });
    }

    /**
     * @return Season
     */
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

    /**
     * Get the season that was active at a specific date.
     * @param $date Carbon
     * @param Expansion|null $expansion
     * @return Season|null
     */
    public function getSeasonAt(Carbon $date, ?Expansion $expansion = null): ?Season
    {
        if ($expansion === null) {
            $expansion = $this->expansionService->getCurrentExpansion();
        }

        /** @var Season $season */
        $season = Season::where('start', '<', $date)
            ->where('expansion_id', $expansion->id)
            ->orderBy('start', 'desc')
            ->limit(1)
            ->first();

        if ($season === null) {
            logger()->error('Season is null for date', [
                'date' => $date,
            ]);
        }

        return $season;
    }

    /**
     * @param Expansion|null $expansion The expansion you want the current season for - or null to get it for the current expansion.
     * @return Season|null The season that's currently active, or null if none is active at this time.
     */
    public function getCurrentSeason(?Expansion $expansion = null): ?Season
    {
        if ($expansion === null) {
            $expansion = $this->expansionService->getCurrentExpansion();
        }

        return $this->getSeasonAt($this->getUserNow(), $expansion);
    }

    /**
     * @param $date Carbon The date at which you want to know the full iterations that have been done since then.
     * @return int The amount of iterations done since all time starting Mythic Plus.
     */
    public function getIterationsAt(Carbon $date): int
    {
        $seasonsStart = $this->getFirstSeason();

        $weeksSinceStart = $seasonsStart->getWeeksSinceStartAt($date);

        // Round down
        return (int)($weeksSinceStart / config('keystoneguru.season_iteration_affix_group_count'));
    }

    /**
     * Get the index in the list of affix groups that we're currently at. Each season has 12 affix groups per iteration.
     * We can calculate where exactly we are in the current iteration, we just don't know the affix group that represents
     * that index, that's up to the current season.
     *
     * @param Carbon $date
     * @return int
     * @throws Exception
     */
    public function getAffixGroupIndexAt(Carbon $date): int
    {
        $iterationsSinceDate = $this->getIterationsAt($date);

        $season      = $this->getFirstSeason();
        $currentDate = $season->start();
        $currentDate->addWeeks($iterationsSinceDate * config('keystoneguru.season_iteration_affix_group_count'));

        if ($currentDate->gt($date)) {
            throw new Exception('Iteration calculation is wrong; cannot find the affix group at a specific time because the current date is past the target date!');
        }

        // Have to backtrack once; since the date is now bigger than the previous date
        return $currentDate->diffInWeeks($date);
    }

    /**
     * Get the affix groups that should be displayed in a table in the /affixes page.
     *
     * @param $iterationOffset int An optional offset to display affixes in the past or future.
     * @return Collection
     * @throws Exception
     * @todo This can be further improved with some mathy things, but for now it's quick enough
     */
    public function getDisplayedAffixGroups(int $iterationOffset): Collection
    {
        $seasons = Season::selectRaw('seasons.*')
            ->leftJoin('timewalking_events', 'timewalking_events.expansion_id', 'seasons.expansion_id')
            ->whereNull('timewalking_events.id')
            ->orderBy('start')->get();

        /** @var Season $currentSeason */
        $currentSeason = $seasons->shift();
        /** @var Season $nextSeason */
        $nextSeason       = $seasons->shift();
        $firstSeasonStart = $currentSeason->start();
        $affixCount       = config('keystoneguru.season_iteration_affix_group_count');


        // Ensure that we cannot go beyond the start of the first season - there's nothing before that
        $beginDate           = $this->getUserNow()->addWeeks($iterationOffset * $affixCount)->maximum($firstSeasonStart);
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
        // This formula should be changed if there's seasons which deviate from the usual amount of affix groups in an
        // iteration (currently 12).

        $weeksSinceBeginning = (floor($weeksSinceBeginning / $affixCount) + 1) * $affixCount;

        $affixGroups = new Collection();
        // Add two weeks so that we can show an additional affix before and after the list so that we can always see what's coming up next
        $affixCountToReturn   = $affixCount + 2;
        $simulatedTime        = $firstSeasonStart->copy();
        $totalWeeksToSimulate = $weeksSinceBeginning + 1;
        for ($i = 0; $i < $totalWeeksToSimulate; $i++) {
            if ($nextSeason !== null) {
                // If we should switch to the next season...
                if ($simulatedTime->gt($nextSeason->start())) {
                    // Move to the next season
                    $currentSeason = $nextSeason;
                    $nextSeason    = $seasons->shift();
                }
            }

            // Keep this affix group (or not)
            if (($totalWeeksToSimulate - $i) <= $affixCountToReturn) {
                // Append to the list of when we have which affix groups
                $affixGroups->push([
                    'date_start' => $simulatedTime->copy(),
                    'affixgroup' => $currentSeason->affixgroups[$i % $affixCount],
                ]);
            }

            // Add another week and continue..
            $simulatedTime->addWeek();
        }

        return $affixGroups;
//        }, config('keystoneguru.cache.displayed_affix_groups.ttl'));
    }
}
