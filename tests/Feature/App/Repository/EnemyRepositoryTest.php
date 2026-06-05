<?php

namespace Tests\Feature\App\Repository;

use App\Models\Enemy;
use App\Repositories\Database\EnemyRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Fixtures\DungeonFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('EnemyRepository')]
final class EnemyRepositoryTest extends PublicTestCase
{
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
        $dungeon = DungeonFixtures::getDungeonWithCurrentMappingVersionWithEnemies();

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
        $dungeon = DungeonFixtures::getDungeonWithCurrentMappingVersionWithEnemies();

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
        $dungeon = DungeonFixtures::getDungeonWithCurrentMappingVersionWithEnemies();

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
        $dungeon = DungeonFixtures::getDungeonWithCurrentMappingVersionWithEnemies();

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($mappingVersion, 'No current mapping version found for test dungeon.');

        // Act
        $result = $this->repository->getAvailableEnemiesForDungeonRouteBuilder($mappingVersion);

        // Assert — every enemy must have kill_priority set (defaulted to 0 if null)
        $result->each(function (Enemy $enemy) {
            $this->assertNotNull($enemy->kill_priority, sprintf('Enemy %d has null kill_priority.', $enemy->id));
        });
    }
}
