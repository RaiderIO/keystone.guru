<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * hasKilledAllRequiredEnemies() had its entire body commented out and unconditionally returned true (#3666),
 * because the original implementation walked $this->dungeon->enemies - a hasManyThrough over floors that is NOT
 * scoped to a mapping version. It therefore saw the required enemies of *every* mapping version the dungeon ever
 * had, including enemies that no longer exist on the route's own map and can never be killed by it. On the seeded
 * data that permanently blocked publishing for 10 active dungeons, which is why the check was disabled instead of
 * fixed. It now walks the route's own mapping version, so these tests deliberately place required enemies in a
 * *different* mapping version of the same dungeon to prove the scoping.
 */
#[Group('DungeonRoute')]
final class DungeonRouteHasKilledAllRequiredEnemiesTest extends PublicTestCase
{
    #[Test]
    public function hasKilledAllRequiredEnemies_givenNoRequiredEnemiesInMappingVersion_returnsTrue(): void
    {
        // Arrange
        $route = $this->createRouteWithOwnMappingVersion();

        try {
            $this->createEnemy($route->mappingVersion, required: false);

            // Act
            $result = $route->fresh()->hasKilledAllRequiredEnemies();

            // Assert
            $this->assertTrue($result);
        } finally {
            $this->cleanup($route);
        }
    }

    #[Test]
    public function hasKilledAllRequiredEnemies_givenRequiredEnemyNotKilled_returnsFalse(): void
    {
        // Arrange
        $route = $this->createRouteWithOwnMappingVersion();

        try {
            $this->createEnemy($route->mappingVersion, required: true);

            // Act
            $result = $route->fresh()->hasKilledAllRequiredEnemies();

            // Assert
            $this->assertFalse($result);
        } finally {
            $this->cleanup($route);
        }
    }

    #[Test]
    public function hasKilledAllRequiredEnemies_givenRequiredEnemyKilled_returnsTrue(): void
    {
        // Arrange
        $route = $this->createRouteWithOwnMappingVersion();

        try {
            $enemy = $this->createEnemy($route->mappingVersion, required: true);
            KillZone::factory()
                ->withEnemies($enemy)
                ->create(['dungeon_route_id' => $route->id, 'floor_id' => $enemy->floor_id]);

            // Act
            $result = $route->fresh()->hasKilledAllRequiredEnemies();

            // Assert
            $this->assertTrue($result);
        } finally {
            $this->cleanup($route);
        }
    }

    #[Test]
    public function hasKilledAllRequiredEnemies_givenRequiredEnemyOnlyInOtherMappingVersion_returnsTrue(): void
    {
        // Arrange - this is the #3666 regression: a required enemy belonging to a *different* mapping version of the
        // same dungeon must not be held against this route. The route's own map does not even contain this enemy.
        $route               = $this->createRouteWithOwnMappingVersion();
        $otherMappingVersion = $this->createMappingVersion($route);

        try {
            $this->createEnemy($otherMappingVersion, required: true);

            // Act
            $result = $route->fresh()->hasKilledAllRequiredEnemies();

            // Assert
            $this->assertTrue($result);
        } finally {
            $this->cleanup($route);
            Enemy::query()->where('mapping_version_id', $otherMappingVersion->id)->delete();
            MappingVersion::query()->where('id', $otherMappingVersion->id)->delete();
        }
    }

    #[Test]
    public function hasKilledAllRequiredEnemies_givenUnkilledTeemingOnlyRequiredEnemyOnNonTeemingRoute_returnsTrue(): void
    {
        // Arrange - a 'visible' enemy only spawns on teeming keys, so it cannot be required of a non-teeming route
        $route = $this->createRouteWithOwnMappingVersion(['teeming' => false]);

        try {
            $this->createEnemy($route->mappingVersion, required: true, teeming: Enemy::TEEMING_VISIBLE);

            // Act
            $result = $route->fresh()->hasKilledAllRequiredEnemies();

            // Assert
            $this->assertTrue($result);
        } finally {
            $this->cleanup($route);
        }
    }

    #[Test]
    public function hasKilledAllRequiredEnemies_givenUnkilledTeemingOnlyRequiredEnemyOnTeemingRoute_returnsFalse(): void
    {
        // Arrange
        $route = $this->createRouteWithOwnMappingVersion(['teeming' => true]);

        try {
            $this->createEnemy($route->mappingVersion, required: true, teeming: Enemy::TEEMING_VISIBLE);

            // Act
            $result = $route->fresh()->hasKilledAllRequiredEnemies();

            // Assert
            $this->assertFalse($result);
        } finally {
            $this->cleanup($route);
        }
    }

    #[Test]
    public function hasKilledAllRequiredEnemies_givenUnkilledTeemingHiddenRequiredEnemyOnTeemingRoute_returnsTrue(): void
    {
        // Arrange - a 'hidden' enemy is despawned on teeming keys, so it cannot be required of a teeming route.
        // Note this is Enemy::TEEMING_HIDDEN - both the commented out PHP and the live JS checked for the string
        // 'invisible', which is not a value this column ever holds.
        $route = $this->createRouteWithOwnMappingVersion(['teeming' => true]);

        try {
            $this->createEnemy($route->mappingVersion, required: true, teeming: Enemy::TEEMING_HIDDEN);

            // Act
            $result = $route->fresh()->hasKilledAllRequiredEnemies();

            // Assert
            $this->assertTrue($result);
        } finally {
            $this->cleanup($route);
        }
    }

    /**
     * Creates a route on a mapping version of its own, so that enemies created for it cannot collide with the
     * seeded mapping data of the dungeon it happens to land on.
     *
     * @param array<string, mixed> $overrides
     */
    private function createRouteWithOwnMappingVersion(array $overrides = []): DungeonRoute
    {
        $route = DungeonRoute::factory()->create(array_merge(['teeming' => false], $overrides));

        $route->update(['mapping_version_id' => $this->createMappingVersion($route)->id]);

        return $route->fresh();
    }

    /**
     * A brand new, empty mapping version for the route's dungeon, based on the one the route currently points at.
     */
    private function createMappingVersion(DungeonRoute $route): MappingVersion
    {
        $current = $route->mappingVersion;

        return MappingVersion::create([
            'game_version_id'                 => $current->game_version_id,
            'dungeon_id'                      => $route->dungeon_id,
            'version'                         => $current->version + 1,
            'enemy_forces_required'           => $current->enemy_forces_required,
            'enemy_forces_required_teeming'   => $current->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $current->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $current->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $current->timer_max_seconds,
        ]);
    }

    private function createEnemy(MappingVersion $mappingVersion, bool $required, ?string $teeming = null): Enemy
    {
        return Enemy::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $mappingVersion->dungeon->floors->first()->id,
            'npc_id'             => null,
            'teeming'            => $teeming,
            'required'           => $required,
            'lat'                => 0,
            'lng'                => 0,
        ]);
    }

    /**
     * Enemy and MappingVersion are SeederModels, whose delete() is silently refused - clean them up through the
     * query builder instead.
     */
    private function cleanup(DungeonRoute $route): void
    {
        $mappingVersionId = $route->mapping_version_id;

        $route->killZones()->each(static fn(KillZone $killZone) => $killZone->delete());
        $route->delete();

        Enemy::query()->where('mapping_version_id', $mappingVersionId)->delete();
        MappingVersion::query()->where('id', $mappingVersionId)->delete();
    }
}
