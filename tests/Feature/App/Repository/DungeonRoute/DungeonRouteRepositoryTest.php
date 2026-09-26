<?php

namespace Tests\Feature\App\Repository\DungeonRoute;

use App\Models\Affix;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Models\User;
use App\Repositories\Database\DungeonRoute\Dtos\KillZoneEnemyForces;
use App\Repositories\Database\DungeonRoute\DungeonRouteRepository;
use App\Repositories\Interfaces\DungeonRoute\Dtos\DungeonRouteSearchFilter;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRouteRepository')]
final class DungeonRouteRepositoryTest extends PublicTestCase
{
    private DungeonRouteRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DungeonRouteRepository(
            app()->make(SeasonServiceInterface::class),
        );
    }

    #[Test]
    public function generateRandomPublicKey_givenNoArguments_returnsNonEmptyString(): void
    {
        // Act
        $result = $this->repository->generateRandomPublicKey();

        // Assert
        $this->assertNotEmpty($result);
    }

    #[Test]
    public function generateRandomPublicKey_givenMultipleCalls_returnsUniqueKeys(): void
    {
        // Act
        $keys = collect(range(1, 10))->map(fn() => $this->repository->generateRandomPublicKey());

        // Assert
        $this->assertEquals($keys->count(), $keys->unique()->count(), 'Generated public keys are not unique.');
    }

    #[Test]
    public function findCombatLogRouteByPublicKey_givenNullKey_returnsNull(): void
    {
        // Act
        $result = $this->repository->findCombatLogRouteByPublicKey(null);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function findCombatLogRouteByPublicKey_givenNonExistentKey_returnsNull(): void
    {
        // Act
        $result = $this->repository->findCombatLogRouteByPublicKey('__nonexistent_public_key__');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[SlowTest]
    public function findRoutes_givenFilter_returnsCollection(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create();

        try {
            $filter = new DungeonRouteSearchFilter($dungeonRoute->mappingVersion);

            // Act
            $result = $this->repository->findRoutes($filter);

            // Assert
            $this->assertInstanceOf(Collection::class, $result);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function findRoutes_givenTitleFilter_returnsOnlyMatchingRoutes(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create([
            'title'      => 'UniqueTestRouteTitle12345',
            'expires_at' => null,
        ]);

        try {
            $filter = new DungeonRouteSearchFilter(
                mappingVersion: $dungeonRoute->mappingVersion,
                title: 'UniqueTestRouteTitle12345',
            );

            // Act
            $result = $this->repository->findRoutes($filter);

            // Assert
            $this->assertNotEmpty($result);
            $result->each(function (DungeonRoute $route) {
                $this->assertStringContainsStringIgnoringCase('UniqueTestRouteTitle12345', $route->title);
            });
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function findRoutes_givenKeyLevelFilter_returnsRoutesWithinRange(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create([
            'level_min' => 10,
            'level_max' => 15,
        ]);

        try {
            $filter = new DungeonRouteSearchFilter(
                mappingVersion: $dungeonRoute->mappingVersion,
                minKeyLevel: 10,
                maxKeyLevel: 15,
            );

            // Act
            $result = $this->repository->findRoutes($filter);

            // Assert
            $this->assertInstanceOf(Collection::class, $result);
            $result->each(function (DungeonRoute $route) {
                $this->assertGreaterThanOrEqual(10, $route->level_min);
                $this->assertLessThanOrEqual(15, $route->level_max);
            });
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getEnemyForcesPerKillZone_givenNonExistentRoute_returnsEmptyCollection(): void
    {
        // Arrange
        $dungeonRoute = new DungeonRoute();

        // Act
        $result = $this->repository->getEnemyForcesPerKillZone($dungeonRoute);

        // Assert
        $this->assertTrue($result->isEmpty());
    }

    #[Test]
    public function getEnemyForcesPerKillZone_givenRouteWithNoKillZones_returnsEmptyCollection(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create();

        try {
            // Act
            $result = $this->repository->getEnemyForcesPerKillZone($dungeonRoute);

            // Assert
            $this->assertTrue($result->isEmpty());
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getEnemyForcesPerKillZoneForRoutes_givenEmptyCollection_returnsEmptyCollection(): void
    {
        // Act
        $result = $this->repository->getEnemyForcesPerKillZoneForRoutes(collect());

        // Assert
        $this->assertTrue($result->isEmpty());
    }

    #[Test]
    public function getEnemyForcesPerKillZoneForRoutes_givenRoutesWithoutKillZones_returnsEmptyCollection(): void
    {
        // Arrange
        $dungeonRoutes = DungeonRoute::factory()->count(3)->create();

        try {
            // Act
            $result = $this->repository->getEnemyForcesPerKillZoneForRoutes($dungeonRoutes);

            // Assert
            $this->assertTrue($result->isEmpty());
        } finally {
            $dungeonRoutes->each(fn(DungeonRoute $dungeonRoute) => $dungeonRoute->delete());
        }
    }

    #[Test]
    public function getEnemyForcesPerKillZoneForRoutes_givenRoutesWithKillZones_matchesPerRouteResults(): void
    {
        // Arrange - every seeded route that already carries kill zones/enemies, batched together
        $dungeonRoutes = DungeonRoute::query()
            ->has('killZones')
            ->with(['affixes', 'season.expansion'])
            ->get();

        $this->assertNotEmpty($dungeonRoutes, 'Expected some seeded routes with kill zones');

        // Act
        $batchedResult = $this->repository->getEnemyForcesPerKillZoneForRoutes($dungeonRoutes);

        // Assert - the batched call returns the exact same per-pull forces as the single-route call
        $sawNonZeroForces = false;
        $sawBoss          = false;
        $dungeonRoutes->each(function (DungeonRoute $dungeonRoute) use ($batchedResult, &$sawNonZeroForces, &$sawBoss) {
            $singleResult    = $this->repository->getEnemyForcesPerKillZone($dungeonRoute);
            $batchedForRoute = $batchedResult->get($dungeonRoute->id, collect());

            $sawNonZeroForces = $sawNonZeroForces || $singleResult->contains(fn(KillZoneEnemyForces $forces) => $forces->enemyForces > 0);
            $sawBoss          = $sawBoss || $singleResult->contains(fn(KillZoneEnemyForces $forces) => $forces->hasBoss);

            $this->assertEquals(
                $singleResult->map(fn(KillZoneEnemyForces $forces) => [$forces->enemyForces, $forces->hasBoss])->values()->all(),
                $batchedForRoute->map(fn(KillZoneEnemyForces $forces) => [$forces->enemyForces, $forces->hasBoss])->values()->all(),
                sprintf('Mismatch for dungeon route %d', $dungeonRoute->id),
            );
        });

        // Guards against a vacuous pass - the seeded routes must actually exercise nonzero forces/bosses
        $this->assertTrue($sawNonZeroForces, 'Expected at least one seeded route with nonzero enemy forces');
        $this->assertTrue($sawBoss, 'Expected at least one seeded route with a boss pull');
    }

    #[Test]
    public function getEnemyForcesPerKillZoneForRoutes_givenShroudedAndNonShroudedRoutes_matchesPerRouteResultsForBoth(): void
    {
        // Arrange - split the seeded routes into the two buckets the batched query fires separately
        $dungeonRoutes = DungeonRoute::query()
            ->has('killZones')
            ->with(['affixes', 'season.expansion'])
            ->get();

        $shroudedRoutes    = $dungeonRoutes->filter(fn(DungeonRoute $r) => $r->getSeasonalAffix()?->key === Affix::AFFIX_SHROUDED);
        $nonShroudedRoutes = $dungeonRoutes->reject(fn(DungeonRoute $r) => $r->getSeasonalAffix()?->key === Affix::AFFIX_SHROUDED);

        $this->assertNotEmpty($shroudedRoutes, 'Expected some seeded shrouded routes with kill zones');
        $this->assertNotEmpty($nonShroudedRoutes, 'Expected some seeded non-shrouded routes with kill zones');

        // Act - both buckets batched together in a single call, exercising the union() of the two queries
        $batchedResult = $this->repository->getEnemyForcesPerKillZoneForRoutes($dungeonRoutes);

        // Assert - neither bucket is dropped or duplicated; every route still matches its single-route result
        $dungeonRoutes->each(function (DungeonRoute $dungeonRoute) use ($batchedResult) {
            $singleResult    = $this->repository->getEnemyForcesPerKillZone($dungeonRoute);
            $batchedForRoute = $batchedResult->get($dungeonRoute->id, collect());

            $this->assertEquals(
                $singleResult->map(fn(KillZoneEnemyForces $forces) => [$forces->enemyForces, $forces->hasBoss])->values()->all(),
                $batchedForRoute->map(fn(KillZoneEnemyForces $forces) => [$forces->enemyForces, $forces->hasBoss])->values()->all(),
                sprintf('Mismatch for dungeon route %d', $dungeonRoute->id),
            );
        });
    }

    #[Test]
    #[DataProvider('getDungeonRoutesWithExpiredThumbnails_scheduledSweepProvider')]
    public function getDungeonRoutesWithExpiredThumbnails_givenScheduledSweep_returnsOnlyRecentlyEditedStaleRoutes(
        int  $updatedMinutesAgo,
        int  $thumbnailUpdatedMinutesAgo,
        ?int $queuedMinutesAgo,
        int  $popularity,
        bool $expectedPicked,
    ): void {
        // Arrange
        config(['keystoneguru.thumbnail.refresh_outdated_count' => 100000]);
        $dungeonRoute = null;

        try {
            $dungeonRoute = $this->createRouteWithThumbnailState(
                $updatedMinutesAgo,
                $thumbnailUpdatedMinutesAgo,
                $queuedMinutesAgo,
                $popularity,
            );

            // Act
            $result = $this->repository->getDungeonRoutesWithExpiredThumbnails();

            // Assert
            $this->assertSame($expectedPicked, $result->pluck('id')->contains($dungeonRoute->id));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    /**
     * @return array<string, array{int, int, int|null, int, bool}>
     */
    public static function getDungeonRoutesWithExpiredThumbnails_scheduledSweepProvider(): array
    {
        $hour = 60;
        $day  = 24 * $hour;

        return [
            'popular route edited an hour ago'                   => [$hour, 2 * $day, null, 10, true],
            'popular route edited beyond the recent-edit window' => [8 * $day, 30 * $day, null, 10, false],
            'popular route edited within the debounce'           => [5, 2 * $day, null, 10, false],
            'unpopular route edited an hour ago'                 => [$hour, 2 * $day, null, 0, false],
            'route queued within the requeue window'             => [2 * $day, 3 * $day, 13 * $hour, 10, false],
            'route queued beyond the requeue window'             => [5 * $day, 6 * $day, 73 * $hour, 10, true],
            'route whose thumbnail is newer than its last edit'  => [2 * $day, $day, null, 10, false],
        ];
    }

    #[Test]
    public function getDungeonRoutesWithExpiredThumbnails_givenOldUnpopularStaleRouteInList_returnsRoute(): void
    {
        // Arrange
        $dungeonRoute = null;

        try {
            $dungeonRoute = $this->createRouteWithThumbnailState(30 * 24 * 60, 60 * 24 * 60, null, 0);

            // Act
            $result = $this->repository->getDungeonRoutesWithExpiredThumbnails(collect([$dungeonRoute]));

            // Assert
            $this->assertTrue($result->pluck('id')->contains($dungeonRoute->id));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoutesWithExpiredThumbnails_givenListedRouteWithoutStandardThumbnail_returnsRoute(): void
    {
        // Arrange - the timestamps say the thumbnail is fresh, but no thumbnail row backs it
        $dungeonRoute = null;

        try {
            $dungeonRoute = $this->createRouteWithThumbnailState(2 * 24 * 60, 24 * 60, null, 0);

            // Act
            $result = $this->repository->getDungeonRoutesWithExpiredThumbnails(collect([$dungeonRoute]));

            // Assert
            $this->assertTrue($result->pluck('id')->contains($dungeonRoute->id));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoutesWithExpiredThumbnails_givenListedRouteWithFreshStandardThumbnail_doesNotReturnRoute(): void
    {
        // Arrange
        $dungeonRoute = null;
        $thumbnail    = null;

        try {
            $dungeonRoute = $this->createRouteWithThumbnailState(2 * 24 * 60, 24 * 60, null, 0);
            $thumbnail    = DungeonRouteThumbnail::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $dungeonRoute->dungeon->floors()->firstOrFail()->id,
                'variant'          => DungeonRouteThumbnailVariant::Standard,
            ]);

            // Act
            $result = $this->repository->getDungeonRoutesWithExpiredThumbnails(collect([$dungeonRoute]));

            // Assert
            $this->assertFalse($result->pluck('id')->contains($dungeonRoute->id));
        } finally {
            $thumbnail?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoutesWithExpiredThumbnails_givenListedRouteWithOnlyHeroThumbnail_returnsRoute(): void
    {
        // Arrange
        $dungeonRoute = null;
        $thumbnail    = null;

        try {
            $dungeonRoute = $this->createRouteWithThumbnailState(2 * 24 * 60, 24 * 60, null, 0);
            $thumbnail    = DungeonRouteThumbnail::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $dungeonRoute->dungeon->floors()->firstOrFail()->id,
                'variant'          => DungeonRouteThumbnailVariant::Hero,
            ]);

            // Act
            $result = $this->repository->getDungeonRoutesWithExpiredThumbnails(collect([$dungeonRoute]));

            // Assert
            $this->assertTrue($result->pluck('id')->contains($dungeonRoute->id));
        } finally {
            $thumbnail?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    #[DataProvider('stampLastAccessedAt_notAccessedTodayProvider')]
    public function stampLastAccessedAt_givenRouteNotAccessedToday_stampsNowWithoutTouchingUpdatedAt(?string $lastAccessedAt): void
    {
        // Arrange
        $dungeonRoute = null;

        try {
            $dungeonRoute = $this->createRouteWithThumbnailState(3 * 24 * 60, 4 * 24 * 60, null, 0);
            DungeonRoute::query()->whereKey($dungeonRoute->id)->toBase()->update(['last_accessed_at' => $lastAccessedAt]);
            $updatedAt = $dungeonRoute->updated_at->toDateTimeString();

            // Act
            $result = $this->repository->stampLastAccessedAt(collect([$dungeonRoute->id]));

            // Assert
            $dungeonRoute->refresh();
            $this->assertSame(1, $result);
            $this->assertTrue($dungeonRoute->last_accessed_at->isToday());
            $this->assertSame($updatedAt, $dungeonRoute->updated_at->toDateTimeString());
        } finally {
            $dungeonRoute?->delete();
        }
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function stampLastAccessedAt_notAccessedTodayProvider(): array
    {
        return [
            'never accessed'          => [null],
            'last accessed yesterday' => [now()->subDay()->toDateTimeString()],
        ];
    }

    #[Test]
    public function stampLastAccessedAt_givenRouteAlreadyAccessedToday_doesNotWrite(): void
    {
        // Arrange
        $dungeonRoute = null;

        try {
            $dungeonRoute   = $this->createRouteWithThumbnailState(3 * 24 * 60, 4 * 24 * 60, null, 0);
            $lastAccessedAt = now()->startOfDay()->toDateTimeString();
            DungeonRoute::query()->whereKey($dungeonRoute->id)->toBase()->update(['last_accessed_at' => $lastAccessedAt]);

            // Act
            $result = $this->repository->stampLastAccessedAt(collect([$dungeonRoute->id]));

            // Assert
            $dungeonRoute->refresh();
            $this->assertSame(0, $result);
            $this->assertSame($lastAccessedAt, $dungeonRoute->last_accessed_at->toDateTimeString());
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function stampLastAccessedAt_givenNoRoutes_returnsZero(): void
    {
        // Act
        $result = $this->repository->stampLastAccessedAt(collect());

        // Assert
        $this->assertSame(0, $result);
    }

    #[Test]
    public function hasNonSandboxRoutesByAuthor_givenAnOwnedRoute_returnsTrue(): void
    {
        // Arrange
        $user         = User::factory()->create();
        $dungeonRoute = DungeonRoute::factory()->create(['author_id' => $user->id, 'expires_at' => null]);

        try {
            // Act
            $result = $this->repository->hasNonSandboxRoutesByAuthor($user);

            // Assert
            $this->assertTrue($result);
        } finally {
            $dungeonRoute->delete();
            $user->delete();
        }
    }

    #[Test]
    public function hasNonSandboxRoutesByAuthor_givenOnlySandboxRoutes_returnsFalse(): void
    {
        // Arrange
        $user         = User::factory()->create();
        $dungeonRoute = DungeonRoute::factory()->create(['author_id' => $user->id, 'expires_at' => now()->addHour()]);

        try {
            // Act
            $result = $this->repository->hasNonSandboxRoutesByAuthor($user);

            // Assert
            $this->assertFalse($result);
        } finally {
            $dungeonRoute->delete();
            $user->delete();
        }
    }

    #[Test]
    public function hasNonSandboxRoutesByAuthor_givenOnlySomeoneElsesRoute_returnsFalse(): void
    {
        // Arrange
        $user         = User::factory()->create();
        $otherUser    = User::factory()->create();
        $dungeonRoute = DungeonRoute::factory()->create(['author_id' => $otherUser->id, 'expires_at' => null]);

        try {
            // Act
            $result = $this->repository->hasNonSandboxRoutesByAuthor($user);

            // Assert
            $this->assertFalse($result);
        } finally {
            $dungeonRoute->delete();
            $otherUser->delete();
            $user->delete();
        }
    }

    #[Test]
    public function hasNonSandboxRoutesByAuthor_givenNoRoutes_returnsFalse(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            // Act
            $result = $this->repository->hasNonSandboxRoutesByAuthor($user);

            // Assert
            $this->assertFalse($result);
        } finally {
            $user->delete();
        }
    }

    private function createRouteWithThumbnailState(
        int  $updatedMinutesAgo,
        int  $thumbnailUpdatedMinutesAgo,
        ?int $queuedMinutesAgo,
        int  $popularity,
    ): DungeonRoute {
        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'  => 1,
            'expires_at' => null,
        ]);

        // Written through the query builder so Eloquent does not overwrite updated_at
        DungeonRoute::query()->whereKey($dungeonRoute->id)->toBase()->update([
            'popularity'                  => $popularity,
            'updated_at'                  => now()->subMinutes($updatedMinutesAgo)->toDateTimeString(),
            'thumbnail_updated_at'        => now()->subMinutes($thumbnailUpdatedMinutesAgo)->toDateTimeString(),
            'thumbnail_refresh_queued_at' => $queuedMinutesAgo === null
                ? '1970-01-01 00:00:00'
                : now()->subMinutes($queuedMinutesAgo)->toDateTimeString(),
        ]);

        return $dungeonRoute->refresh();
    }
}
