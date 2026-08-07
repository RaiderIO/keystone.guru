<?php

namespace Tests\Feature\App\Repository\DungeonRoute;

use App\Models\Affix;
use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Database\DungeonRoute\Dtos\KillZoneEnemyForces;
use App\Repositories\Database\DungeonRoute\DungeonRouteRepository;
use App\Repositories\Interfaces\DungeonRoute\Dtos\DungeonRouteSearchFilter;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;
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
}
