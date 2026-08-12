<?php

namespace Tests\Feature\Traits;

use App\Models\Season;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Hides every *seeded* upcoming season for the duration of a test.
 *
 * "Upcoming season" resolution (HeaderComposer, CreateRouteFormComposer, the explore season tabs) answers
 * a question about global state: which is the soonest upcoming season, and is any advertised at all. A test
 * that creates its own upcoming season and asserts on that answer therefore only works while no *seeded*
 * season happens to be upcoming too - and seasons are seeded weeks before they start, so one usually is.
 * When a seeded season starts sooner than the test's own (Midnight S2 moved to 2026-08-17 in #3980), the
 * assertions silently describe the seeded season instead, and "no season is advertised" cannot hold at all.
 *
 * Rather than chase the seeded dates, these tests take ownership of the upcoming-season set: every seeded
 * season that has not started yet is parked far in the future and deactivated, and restored afterwards.
 */
trait IsolatesSeededUpcomingSeasons
{
    /** @var array<int, array{start: string, active: int}> */
    private array $parkedUpcomingSeasons = [];

    /**
     * Parks every currently-upcoming season. Call from setUp(), before the test creates its own season -
     * seasons created afterwards are deliberately untouched, they are the test's subject.
     */
    protected function hideSeededUpcomingSeasons(): void
    {
        $this->parkedUpcomingSeasons = [];

        $upcomingSeasons = DB::table('seasons')
            ->select(['id', 'start', 'active'])
            ->where('start', '>', now()->toDateTimeString())
            ->get();

        foreach ($upcomingSeasons as $season) {
            $this->parkedUpcomingSeasons[(int)$season->id] = [
                'start'  => $season->start,
                'active' => (int)$season->active,
            ];

            DB::table('seasons')
                ->where('id', $season->id)
                // Far enough out that it cannot win "soonest upcoming" against any date a test picks
                ->update(['start' => now()->addYears(50)->toDateTimeString(), 'active' => 0]);
        }

        $this->flushSeededSeasonCaches();
    }

    /**
     * Restores the parked seasons. Call from tearDown() - the test database persists between runs, so
     * leaving a season parked would corrupt every later test rather than just this one.
     */
    protected function restoreSeededUpcomingSeasons(): void
    {
        foreach ($this->parkedUpcomingSeasons as $seasonId => $attributes) {
            DB::table('seasons')->where('id', $seasonId)->update($attributes);
        }

        $this->parkedUpcomingSeasons = [];

        $this->flushSeededSeasonCaches();
    }

    /**
     * ViewService caches the season it hands the composers for an hour in the 'tmp_file' store which, unlike
     * the array store the tests run on, survives between test runs - so a parked season would otherwise stay
     * visible, or worse, stay parked in the cache after being restored.
     */
    private function flushSeededSeasonCaches(): void
    {
        Cache::store('tmp_file')->flush();
        new Season()->flushCache();
    }
}
