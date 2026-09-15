<?php

namespace Tests\Feature\Traits;

use App\Models\Season;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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
 * season that still counts as upcoming is parked far in the future and deactivated, then restored.
 */
trait IsolatesSeededUpcomingSeasons
{
    /**
     * Parked seasons are stamped with this exact start rather than a relative one, so a row that was left
     * behind by a run that could not restore (a fatal error, a killed process) is recognisable as parked on
     * the next run instead of being captured as if it were its own original value.
     */
    private const string PARKED_START = '2999-01-01 00:00:00';

    /** @var array<int, array{start: string, active: int}> */
    private array $parkedUpcomingSeasons = [];

    /**
     * Parks every currently-upcoming season. Call from setUp(), before the test creates its own season -
     * seasons created afterwards are deliberately untouched, they are the test's subject.
     */
    protected function hideSeededUpcomingSeasons(): void
    {
        $this->parkedUpcomingSeasons = [];

        // Registered before anything is parked, so a failure part-way through the loop below still restores
        // what it managed to park. PHPUnit skips tearDown() entirely when setUp() throws, so this must not
        // rely on a tearDown() override in the using class either.
        $this->beforeApplicationDestroyed(fn() => $this->restoreSeededUpcomingSeasons());

        foreach ($this->getUpcomingSeasons() as $season) {
            if ($season->start === self::PARKED_START) {
                throw new RuntimeException(sprintf(
                    'Season %d is still parked by an earlier test run that could not restore it; its real start ' .
                    'and active flag are unrecoverable. Re-seed the test database (php artisan db:seed) before ' .
                    'running this suite again.',
                    $season->id,
                ));
            }

            // Recorded before the update, so the restore covers this row even if the update itself throws
            $this->parkedUpcomingSeasons[(int)$season->id] = [
                'start'  => $season->start,
                'active' => (int)$season->active,
            ];

            DB::table('seasons')
                ->where('id', $season->id)
                ->update(['start' => self::PARKED_START, 'active' => 0]);
        }

        $this->flushSeededSeasonCaches();
    }

    /**
     * Restores the parked seasons. Runs automatically via beforeApplicationDestroyed() - the test database
     * persists between runs, so leaving a season parked would corrupt every later run rather than just this
     * test.
     */
    protected function restoreSeededUpcomingSeasons(): void
    {
        if ($this->parkedUpcomingSeasons === []) {
            return;
        }

        foreach ($this->parkedUpcomingSeasons as $seasonId => $attributes) {
            DB::table('seasons')->where('id', $seasonId)->update($attributes);
        }

        $this->parkedUpcomingSeasons = [];

        $this->flushSeededSeasonCaches();
    }

    /**
     * Mirrors `Expansion::nextSeason()`, which treats a season as upcoming until its start plus the acting
     * region's reset offset has passed - so a season stays "next" for up to ~3 days after its start date.
     * Selecting on a plain `start > now()` instead would leave the season unparked, yet still winning
     * `orderBy('start')->limit(1)` against the test's own season, for those few days only.
     *
     * The widest offset across all regions is used so no region is left un-isolated, and it is read from the
     * table rather than hardcoded so seeding a new region cannot silently reopen that window.
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    private function getUpcomingSeasons(): \Illuminate\Support\Collection
    {
        $maxRegionOffsetHours = (int)DB::table('game_server_regions')
            ->max(DB::raw('reset_day_offset * 24 + reset_hours_offset'));

        return DB::table('seasons')
            ->select(['id', 'start', 'active'])
            ->whereRaw('DATE_ADD(`start`, INTERVAL ? hour) >= ?', [$maxRegionOffsetHours, now()])
            ->get();
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
