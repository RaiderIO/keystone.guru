<?php

namespace Tests\Feature\App\Service\Season\SeasonService;

use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Repositories\Interfaces\SeasonRepositoryInterface;
use App\Service\Expansion\ExpansionService;
use App\Service\Season\SeasonService;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('SeasonsOfExpansionCache')]
final class SeasonsOfExpansionCacheTest extends PublicTestCase
{
    #[Test]
    public function getSeasonAt_givenLocalCacheEnabled_readsNoSeasonsInALaterRequest(): void
    {
        // Arrange
        config(['keystoneguru.cache.seasons_of_expansion.enabled' => true]);
        Cache::store('tmp_file')->flush();

        $expansion = Expansion::findOrFail(Expansion::ALL[Expansion::EXPANSION_SHADOWLANDS]);
        $region    = GameServerRegion::getUserOrDefaultRegion();
        $date      = Carbon::create(2022, 9, 1);

        try {
            $this->newSeasonService()->getSeasonAt($date, $expansion, $region);

            // Act
            $result        = null;
            $seasonQueries = $this->countSeasonQueries(function () use (&$result, $date, $expansion, $region): void {
                $result = $this->newSeasonService()->getSeasonAt($date, $expansion, $region);
            });

            // Assert
            $this->assertSame(0, $seasonQueries);
            $this->assertSame(Season::SEASON_SL_S4, $result?->id);
        } finally {
            Cache::store('tmp_file')->flush();
        }
    }

    #[Test]
    public function getSeasonAt_givenLocalCacheDisabled_readsTheSeasonsInEveryRequest(): void
    {
        // Arrange
        config(['keystoneguru.cache.seasons_of_expansion.enabled' => false]);
        Cache::store('tmp_file')->flush();

        $expansion = Expansion::findOrFail(Expansion::ALL[Expansion::EXPANSION_SHADOWLANDS]);
        $region    = GameServerRegion::getUserOrDefaultRegion();
        $date      = Carbon::create(2022, 9, 1);

        try {
            $this->newSeasonService()->getSeasonAt($date, $expansion, $region);

            // Act
            $seasonQueries = $this->countSeasonQueries(function () use ($date, $expansion, $region): void {
                $this->newSeasonService()->getSeasonAt($date, $expansion, $region);
            });

            // Assert
            $this->assertGreaterThan(0, $seasonQueries);
        } finally {
            Cache::store('tmp_file')->flush();
        }
    }

    /**
     * The cache holds the rows, never the answer: which season is current is decided per call, so a
     * cached tree still switches season the moment the next one starts.
     */
    #[Test]
    public function getSeasonAt_givenCachedSeasons_resolvesTheSeasonOfEachDate(): void
    {
        // Arrange
        config(['keystoneguru.cache.seasons_of_expansion.enabled' => true]);
        Cache::store('tmp_file')->flush();

        $expansion = Expansion::findOrFail(Expansion::ALL[Expansion::EXPANSION_SHADOWLANDS]);
        $region    = GameServerRegion::getUserOrDefaultRegion();

        try {
            $seasonS3 = $this->newSeasonService()->getSeasonAt(Carbon::create(2022, 5, 1), $expansion, $region);

            // Act
            $seasonS4 = $this->newSeasonService()->getSeasonAt(Carbon::create(2022, 9, 1), $expansion, $region);

            // Assert
            $this->assertSame(Season::SEASON_SL_S3, $seasonS3?->id);
            $this->assertSame(Season::SEASON_SL_S4, $seasonS4?->id);
        } finally {
            Cache::store('tmp_file')->flush();
        }
    }

    /**
     * A fresh instance stands in for a later request: the service is scoped, so its own per-instance
     * memo would otherwise answer.
     */
    private function newSeasonService(): SeasonService
    {
        return new SeasonService(app(ExpansionService::class), app(SeasonRepositoryInterface::class));
    }

    private function countSeasonQueries(Closure $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            // CI runs with the model cache on, which would answer these queries without reaching the database.
            app('model-cache')->runDisabled($callback);
        } finally {
            DB::disableQueryLog();
        }

        return collect(DB::getQueryLog())
            ->filter(static fn(array $query): bool => str_contains($query['query'], 'from `seasons`'))
            ->count();
    }
}
