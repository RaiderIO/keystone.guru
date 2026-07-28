<?php

namespace Tests\Feature\App\Repository\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Repositories\Database\DungeonRoute\DungeonRouteRepository;
use App\Repositories\Interfaces\DungeonRoute\Dtos\DungeonRouteSearchFilter;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRouteRepository')]
final class DungeonRouteRepositoryTest extends PublicTestCase
{
    use ProvidesDungeon;

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
    public function getRoutesForUserAndDungeon_givenRoutesOnDifferentGameVersions_returnsOnlyTheMatchingGameVersion(): void
    {
        // Arrange - a dungeon with two isolated mapping versions, one per game version, each with its
        // own route by the same user. Regression for #3446: this method used to ignore game version
        // entirely, so a user's route on one game version's mapping could leak into another game
        // version's "Your routes" panel.
        $dungeon                = $this->getDungeonWithNonFacadeFloor();
        $existingMappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($existingMappingVersion, 'Need a dungeon with a current mapping version');

        $retailGameVersion  = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $classicGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);

        $retailMappingVersion  = $this->createIsolatedMappingVersion($dungeon, $existingMappingVersion, $retailGameVersion->id, 1000);
        $classicMappingVersion = $this->createIsolatedMappingVersion($dungeon, $existingMappingVersion, $classicGameVersion->id, 2000);

        $user = User::findOrFail(1);

        $retailRoute = DungeonRoute::factory()->create([
            'author_id'          => $user->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $retailMappingVersion->id,
            'demo'               => false,
            'expires_at'         => null,
        ]);
        $classicRoute = DungeonRoute::factory()->create([
            'author_id'          => $user->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $classicMappingVersion->id,
            'demo'               => false,
            'expires_at'         => null,
        ]);

        try {
            // Act
            $result = $this->repository->getRoutesForUserAndDungeon($user, $dungeon, $retailGameVersion, 10);

            // Assert
            $this->assertTrue($result->contains('id', $retailRoute->id), 'Same-game-version route must be included');
            $this->assertFalse($result->contains('id', $classicRoute->id), 'Different-game-version route must be excluded');
        } finally {
            $retailRoute->delete();
            $classicRoute->delete();
            $retailMappingVersion->delete();
            $classicMappingVersion->delete();
        }
    }

    /**
     * Creates a newer, isolated mapping version for the dungeon on the given game version, so its
     * seeded mapping version becomes "non-current" for that game version.
     */
    private function createIsolatedMappingVersion(Dungeon $dungeon, MappingVersion $existing, int $gameVersionId, int $versionOffset): MappingVersion
    {
        return MappingVersion::create([
            'game_version_id'                 => $gameVersionId,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $existing->version + $versionOffset,
            'enemy_forces_required'           => $existing->enemy_forces_required,
            'enemy_forces_required_teeming'   => $existing->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $existing->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $existing->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $existing->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);
    }
}
