<?php

namespace Tests\Feature\App\Repository\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
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
        $this->assertIsString($result);
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
            'title' => 'UniqueTestRouteTitle12345',
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
}
