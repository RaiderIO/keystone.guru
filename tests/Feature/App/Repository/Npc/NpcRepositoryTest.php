<?php

namespace Tests\Feature\App\Repository\Npc;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcEnemyForces;
use App\Repositories\Database\Npc\NpcRepository;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('NpcRepository')]
final class NpcRepositoryTest extends PublicTestCase
{
    use ProvidesDungeon;

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
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

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
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

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
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

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
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

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
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

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

    #[Test]
    public function getInUseNpcs_givenNpcHasEnemyForcesOnlyForAnotherMappingVersion_stillIncludesNpcForCurrentVersion(): void
    {
        // Arrange — a boss-like NPC that has 0 enemy forces on the mapping version under test (no row
        // at all, which is the normal way a boss is represented) but DOES have a leftover enemy_forces
        // row belonging to a different mapping version of the same dungeon. Regression for the auto
        // route creator silently dropping every boss NPC from a dungeon that had just been re-imported:
        // the unscoped npc_enemy_forces join matched the other version's row instead of NULL, so the
        // "missing == 0 forces, e.g. a boss" fallback never triggered.
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

        $otherMappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($otherMappingVersion, 'No current mapping version found for test dungeon.');

        // A fresh mapping version created off the current one - this is the version under test, and
        // must have no enemy_forces row of its own for the synthetic NPC below.
        $mappingVersionUnderTest = MappingVersion::query()->create([
            'game_version_id' => $otherMappingVersion->game_version_id,
            'dungeon_id'      => $dungeon->id,
            'version'         => $otherMappingVersion->version + 1,
        ]);

        $npcId = 999999001;

        try {
            $npc = Npc::query()->create([
                'id'                => $npcId,
                'classification_id' => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
                'npc_type_id'       => 8,
                'npc_class_id'      => 1,
                'name'              => 'Test Boss - getInUseNpcs regression',
                'aggressiveness'    => 'aggressive',
                'dangerous'         => true,
                'truesight'         => false,
                'runs_away_in_fear' => false,
            ]);

            $dungeon->npcs()->attach($npc->id);

            NpcEnemyForces::query()->create([
                'mapping_version_id' => $otherMappingVersion->id,
                'npc_id'             => $npc->id,
                'enemy_forces'       => 30,
            ]);

            // Act
            $result = $this->repository->getInUseNpcIds($mappingVersionUnderTest);

            // Assert
            $this->assertTrue(
                $result->contains($npc->id),
                'NPC with enemy forces only on a different mapping version must still be included for the version under test.',
            );
        } finally {
            NpcEnemyForces::query()->where('npc_id', $npcId)->delete();
            $dungeon->npcs()->detach($npcId);
            Npc::query()->where('id', $npcId)->delete();
            $mappingVersionUnderTest->delete();
        }
    }
}
