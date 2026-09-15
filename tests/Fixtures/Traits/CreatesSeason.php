<?php

namespace Tests\Fixtures\Traits;

use App\Models\Expansion;
use App\Models\Season;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * A season of the test's own: past by default, upcoming when given a future `start`. The seed promises seasons that
 * have started, never one that has not - a seeded upcoming season becomes current as the clock moves on.
 *
 * Everything created here is deleted again when the test's application is torn down.
 *
 * @mixin TestCase
 */
trait CreatesSeason
{
    /** @var array<int, Season> */
    private array $createdSeasons = [];

    /**
     * @param array<string, mixed> $attributes Any `seasons` column. Defaults to an inactive season that started a year ago, on the first active expansion.
     * @param array<int, int>      $dungeonIds Dungeons to attach through `season_dungeons`.
     */
    protected function createSeason(array $attributes = [], array $dungeonIds = []): Season
    {
        if ($this->createdSeasons === []) {
            $this->beforeApplicationDestroyed(fn() => $this->deleteCreatedSeasons());
        }

        $expansionId = $attributes['expansion_id'] ?? Expansion::query()->where('active', 1)->firstOrFail()->id;

        $season = Season::create(array_merge([
            'expansion_id'            => $expansionId,
            'seasonal_affix_id'       => null,
            'index'                   => (int)Season::query()->where('expansion_id', $expansionId)->max('index') + 1,
            'start'                   => now()->subYear()->toDateTimeString(),
            'active'                  => false,
            'presets'                 => 0,
            'affix_group_count'       => 8,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 25,
            'item_level_min'          => 240,
            'item_level_max'          => 300,
        ], $attributes));

        $this->createdSeasons[] = $season;

        if ($dungeonIds !== []) {
            $season->syncDungeons($dungeonIds);
        }

        $this->flushSeasonCachesForCreatedSeasons();

        return $season;
    }

    /**
     * Runs on teardown by itself; call it directly only when the season must be gone before the test ends.
     */
    protected function deleteCreatedSeasons(): void
    {
        foreach ($this->createdSeasons as $season) {
            // One by one, as Season::syncDungeons() does, so every SeasonDungeon's cache is invalidated
            foreach ($season->seasonDungeons()->get() as $seasonDungeon) {
                $seasonDungeon->delete();
            }

            $season->delete();
        }

        $this->createdSeasons = [];

        $this->flushSeasonCachesForCreatedSeasons();
    }

    /**
     * ViewService keeps the season it hands the composers in the 'tmp_file' store, which outlives the test.
     */
    private function flushSeasonCachesForCreatedSeasons(): void
    {
        Cache::store('tmp_file')->flush();
        new Season()->flushCache();
    }
}
