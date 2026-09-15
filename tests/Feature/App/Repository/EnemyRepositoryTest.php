<?php

namespace Tests\Feature\App\Repository;

use App\Models\Enemy;
use App\Repositories\Database\EnemyRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('EnemyRepository')]
final class EnemyRepositoryTest extends PublicTestCase
{
    use ProvidesDungeon;

    private EnemyRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EnemyRepository();
    }

    #[Test]
    public function getAvailableEnemiesForDungeonRouteBuilder_givenMappingVersion_returnsNonEmptyCollection(): void
    {
        // Arrange
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

        $mappingVersion = $dungeon->getCurrentMappingVersion();

        $this->assertNotNull($mappingVersion, 'No current mapping version found for test dungeon.');

        // Act
        $result = $this->repository->getAvailableEnemiesForDungeonRouteBuilder($mappingVersion);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertInstanceOf(Enemy::class, $result->first());
    }

    #[Test]
    public function getAvailableEnemiesForDungeonRouteBuilder_givenMappingVersion_keysCollectionByEnemyId(): void
    {
        // Arrange
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($mappingVersion, 'No current mapping version found for test dungeon.');

//        dd([$dungeon->key, $mappingVersion->version, $mappingVersion->id]);

        // Act
        $result = $this->repository->getAvailableEnemiesForDungeonRouteBuilder($mappingVersion);

        // Assert — collection must be keyed by enemy ID
        $this->assertNotEmpty($result);
        $firstEnemy = $result->first();
        $this->assertEquals($firstEnemy->id, $result->keys()->first());
    }

    #[Test]
    public function getAvailableEnemiesForDungeonRouteBuilder_givenMappingVersion_excludesMdtPlaceholders(): void
    {
        // Arrange
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($mappingVersion, 'No current mapping version found for test dungeon.');

        // Act
        $result = $this->repository->getAvailableEnemiesForDungeonRouteBuilder($mappingVersion);

        // Assert — MDT placeholder enemies must not appear in results
        $placeholders = $result->filter(
            static fn(Enemy $enemy) => $enemy->seasonal_type === Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER,
        );
        $this->assertEmpty($placeholders, 'MDT placeholder enemies should not be included in the builder collection.');
    }

    #[Test]
    public function getAvailableEnemiesForDungeonRouteBuilder_givenMappingVersion_setsDefaultKillPriority(): void
    {
        // Arrange
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($mappingVersion, 'No current mapping version found for test dungeon.');

        // Act
        $result = $this->repository->getAvailableEnemiesForDungeonRouteBuilder($mappingVersion);

        // Assert — every enemy must have kill_priority set (defaulted to 0 if null)
        $result->each(function (Enemy $enemy) {
            $this->assertNotNull($enemy->kill_priority, sprintf('Enemy %d has null kill_priority.', $enemy->id));
        });
    }

    #[Test]
    public function getAvailableEnemiesForDungeonRouteBuilder_givenMultiplePatrolGroups_ordersByAscendingEnemyPatrolId(): void
    {
        // Arrange
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($mappingVersion, 'No current mapping version found for test dungeon.');

        $baseEnemy = $mappingVersion->enemies()->first();
        $this->assertNotNull($baseEnemy, 'Expected at least one enemy in fixture mapping version.');

        $attributes = $baseEnemy->only($baseEnemy->getFillable());
        unset($attributes['id']);

        // A handful of enemy_patrol_id groups, interspersed with non-patrol (null) enemies, is what
        // exposes the non-transitive comparator bug — Collection::sort() with only one or two
        // distinct values can happen to land in the right place by luck of the sort algorithm.
        $enemyPatrolIds = [null, 3, null, 1, null, 4, null, 2, null, 5, null];

        $createdEnemies = array_map(
            static fn(?int $enemyPatrolId) => Enemy::query()->create(array_merge(
                $attributes,
                ['enemy_patrol_id' => $enemyPatrolId],
            )),
            $enemyPatrolIds,
        );

        try {
            // Act
            $result = $this->repository->getAvailableEnemiesForDungeonRouteBuilder($mappingVersion);

            // Assert — filtered down to just the synthetic enemies, their relative order must be
            // ascending by enemy_patrol_id (null treated as 0), regardless of how the rest sorted.
            $createdIds       = collect($createdEnemies)->pluck('id')->all();
            $orderedPatrolIds = $result->only($createdIds)->map(
                static fn(Enemy $enemy) => $enemy->enemy_patrol_id ?? 0,
            )->values()->all();
            $expectedPatrolIds = $orderedPatrolIds;
            sort($expectedPatrolIds);

            $this->assertCount(count($enemyPatrolIds), $orderedPatrolIds, 'Not all synthetic enemies were found in the result.');
            $this->assertSame($expectedPatrolIds, $orderedPatrolIds, 'Enemies were not ordered ascending by enemy_patrol_id.');
        } finally {
            Enemy::query()->whereKey(collect($createdEnemies)->pluck('id')->all())->delete();
            (new Enemy())->flushCache();
        }
    }
}
