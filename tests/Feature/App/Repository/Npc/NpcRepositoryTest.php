<?php

namespace Tests\Feature\App\Repository\Npc;

use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Repositories\Database\Npc\NpcRepository;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Fixtures\DungeonFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('NpcRepository')]
final class NpcRepositoryTest extends PublicTestCase
{
    private NpcRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new NpcRepository();
    }

    #[Test]
    public function getInUseNpcs_givenMappingVersion_returnsNonEmptyCollection(): void
    {
        // Arrange
        $dungeon = DungeonFixtures::getDungeonWithCurrentMappingVersionWithEnemies();

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($mappingVersion, 'No current mapping version found for test dungeon.');

        // Act
        $result = $this->repository->getInUseNpcs($mappingVersion);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertInstanceOf(Npc::class, $result->first());
    }

    #[Test]
    public function getInUseNpcs_givenMappingVersion_returnsOnlyNpcsForThatDungeon(): void
    {
        // Arrange
        $dungeon = DungeonFixtures::getDungeonWithCurrentMappingVersionWithEnemies();

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($mappingVersion, 'No current mapping version found for test dungeon.');

        // Act
        $result = $this->repository->getInUseNpcs($mappingVersion);

        // Assert — every NPC returned must be linked to this dungeon
        $dungeonNpcIds = $dungeon->npcs()->pluck('npcs.id');
        $result->each(function (Npc $npc) use ($dungeonNpcIds, $dungeon) {
            $this->assertTrue(
                $dungeonNpcIds->contains($npc->id),
                sprintf('NPC %d is not associated with dungeon %s.', $npc->id, $dungeon->key),
            );
        });
    }

    #[Test]
    public function getInUseNpcIds_givenMappingVersion_returnsCollectionOfIntegers(): void
    {
        // Arrange
        $dungeon = DungeonFixtures::getDungeonWithCurrentMappingVersionWithEnemies();

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($mappingVersion, 'No current mapping version found for test dungeon.');

        // Act
        $result = $this->repository->getInUseNpcIds($mappingVersion);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertNotEmpty($result);
        $result->each(function (int $id): void {
            $this->assertGreaterThan(0, $id);
        });
    }

    #[Test]
    public function getInUseNpcIds_givenMappingVersion_alwaysIncludesBrackenhideGnollId(): void
    {
        // Arrange — the Brackenhide Gnoll (194373) is hardcoded into getInUseNpcIds for Witherling conversion
        $dungeon = DungeonFixtures::getDungeonWithCurrentMappingVersionWithEnemies();

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($mappingVersion, 'No current mapping version found for test dungeon.');

        // Act
        $result = $this->repository->getInUseNpcIds($mappingVersion);

        // Assert
        $this->assertTrue($result->contains(194373), 'Brackenhide Gnoll NPC ID 194373 must always be included.');
    }

    #[Test]
    public function getInUseNpcIds_givenPreloadedNpcCollection_usesItInsteadOfQuerying(): void
    {
        // Arrange
        $dungeon = DungeonFixtures::getDungeonWithCurrentMappingVersionWithEnemies();

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($mappingVersion, 'No current mapping version found for test dungeon.');

        $preloadedNpcs = $this->repository->getInUseNpcs($mappingVersion);

        // Act — pass the preloaded NPCs to avoid an extra query
        $resultFromPreloaded = $this->repository->getInUseNpcIds($mappingVersion, $preloadedNpcs);
        $resultFromQuery     = $this->repository->getInUseNpcIds($mappingVersion);

        // Assert — both paths must produce the same set of IDs
        $this->assertEquals(
            $resultFromQuery->sort()->values()->toArray(),
            $resultFromPreloaded->sort()->values()->toArray(),
        );
    }
}
