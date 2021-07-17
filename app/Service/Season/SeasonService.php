<?php

namespace App\Service\Season;


use App\Models\Season;
use App\Service\Cache\CacheService;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * This service provides functionality for reading the current laravel echo service and parsing its contents.
 * @package App\Service\Season
 * @author Wouter
 * @since 17/06/2019
 */
class SeasonService implements SeasonServiceInterface
{
    private $_seasons = null;

    /**
     * @return Carbon Get a date of now with the timezone set properly.
     */
    private function _getNow(): Carbon
    {
        // Find the timezone that makes the most sense
        $timezone = config('app.timezone');

        // But if logged in, get the user's timezone instead
        if (Auth::check()) {
            $timezone = Auth::user()->timezone;
        }

        return Carbon::now($timezone);
    }

    /**
     * @return Collection
     */
    public function getSeasons(): Collection
    {
        if ($this->_seasons === null) {
            $this->_seasons = Season::all();
        }
        return $this->_seasons;
    }

    /**
     * @return Season
     */
    public function getFirstSeason(): Season
    {
        return $this->getSeasons()->first();
    }

    /**
     * Get the season that was active at a specific date.
     * @param $date Carbon
     * @return Season|null
     */
    public function getSeasonAt(Carbon $date): ?Season
    {
        // Find the
        $seasons = $this->getSeasons()->reverse();
        /** @var Season $season By default get the first season - which is the last in the reversed collection */
        $season = $seasons->last();

        foreach ($seasons as $seasonCandidate) {
            /** @var Season $seasonCandidate */
            // Get the season that's the most recent
            if ($date->gte($seasonCandidate->start())) {
                $season = $seasonCandidate;
                break;
            }
        }

        return $season;
    }

    /**
     * @return Season|null The season that's currently active, or null if none is active at this time.
     */
    public function getCurrentSeason(): ?Season
    {
        return $this->getSeasonAt($this->_getNow());
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
     * @param Carbon $date
     * @return int
     * @throws Exception
     */
    public function getAffixGroupIndexAt(Carbon $date): int
    {
        $iterationsSinceDate = $this->getIterationsAt($date);

        $currentDate = $this->getFirstSeason()->start();
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
     *
     * @return Collection
     * @throws Exception
     */
    public function getDisplayedAffixGroups(int $iterationOffset): Collection
    {
        /** @var CacheService $cacheService */
//        $cacheService = App::make(CacheService::class);

//        return $cacheService->remember(sprintf('displayed_affix_groups_%d', $iterationOffset), function () use ($iterationOffset)
//        {
            // Gotta start at the beginning to work out what we should display
            $firstSeason = $this->getFirstSeason();

            // We're going to solve this by starting at the beginning, and then simulating all the M+ weeks so far.
            // Since seasons may start/end at any time during the iteration of affix groups, we need to start at the
            // beginning and add affixes. Once we've simulated everything in the past up until and including the current
            // iteration, we can take off 12 affix groups and return those as those are the affixes we should display!
            $affixCount = config('keystoneguru.season_iteration_affix_group_count');
            // This formula should be changed if there's seasons which deviate from the usual amount of affix groups in an
            // iteration (currently 12).

            $firstSeasonStart = $firstSeason->start();
            $now = $this->_getNow()->addWeeks($iterationOffset * $affixCount)->maximum($firstSeasonStart);
            $weeksSinceBeginning = $firstSeason->getWeeksSinceStartAt($now);


            $weeksSinceBeginning = (floor($weeksSinceBeginning / $affixCount) + 1) * $affixCount;

            $affixGroups = new Collection();
            // Add 1 week so that we can always have a next or previous week on the charts, regardless of where we are.
            // That additional week is used to fetch the first row of the next week
            for ($i = 0; $i < $weeksSinceBeginning + 1; $i++) {
                /** $firstSeasonStart will contain the current date we're iterating on; so it's kinda misleading. This comment should eliminate that */
                $season = $this->getSeasonAt($firstSeasonStart);

                // Get the affix group index
                $affixGroupIndex = $this->getAffixGroupIndexAt($firstSeasonStart);

                $affixGroups->push([
                    'date_start' => $firstSeasonStart->copy(),
                    // Get the actual affix group from the season
                    'affixgroup' => $season->affixgroups[$affixGroupIndex]
                ]);

                // Add another week and continue..
                $firstSeasonStart->addWeek();
            }

            // Subtract TWO weeks since we simulated another week to fetch the first affix of that week.
            // Then, fetch an additional TWO affixes, one to show extra at the top, one to show extra at the bottom
            return $affixGroups->slice($affixGroups->count() - ($affixCount + 2), $affixCount + 2);
//        }, config('keystoneguru.cache.displayed_affix_groups.ttl'));
    }
}