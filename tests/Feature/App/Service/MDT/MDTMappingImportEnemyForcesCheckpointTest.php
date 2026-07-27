<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\EnemyForcesCheckpoint;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCases\PublicTestCase;

/**
 * #3702: an MDT mapping bump used to discard every enemy forces checkpoint of every dungeon, because the
 * enemies it re-creates never got their membership back. MappingService now clones the checkpoints and hands
 * back a source id => clone id mapping, and importEnemies() translates each matched enemy's membership
 * through it - a verbatim copy would attach the enemy to the previous mapping version's checkpoint.
 *
 * importEnemies() is exercised directly (with a stubbed MDTDungeon) rather than through
 * importMappingVersionFromMDT(): a real import parses MDT's Lua and rewrites shared NPC/dungeon rows of the
 * seeded test database, none of which this behaviour depends on.
 */
#[Group('MDT')]
#[Group('MappingVersion')]
#[Group('EnemyForcesCheckpoint')]
final class MDTMappingImportEnemyForcesCheckpointTest extends PublicTestCase
{
    #[Test]
    public function importEnemies_givenClonedEnemyForcesCheckpoints_relinksMatchedEnemiesAndDropsEmptiedCheckpoints(): void
    {
        // Arrange
        $mappingService = $this->app->make(MappingServiceInterface::class);

        $dungeon              = $this->getDungeonWithoutEnemyForcesCheckpoints();
        $sourceMappingVersion = $dungeon->getCurrentMappingVersion();

        [$survivingEnemy, $removedEnemy] = $this->getTwoEnemiesWithDistinctUniqueKeys($sourceMappingVersion);

        // The checkpoint of an enemy MDT still knows about, and the checkpoint of one it no longer does
        $survivingEnemyForcesCheckpoint = $this->createEnemyForcesCheckpoint($sourceMappingVersion, $survivingEnemy, 'Kept corridor');
        $emptiedEnemyForcesCheckpoint   = $this->createEnemyForcesCheckpoint($sourceMappingVersion, $removedEnemy, 'Emptied corridor');

        $survivingEnemy->update(['enemy_forces_checkpoint_id' => $survivingEnemyForcesCheckpoint->id]);
        $removedEnemy->update(['enemy_forces_checkpoint_id' => $emptiedEnemyForcesCheckpoint->id]);

        $newMappingVersion = null;

        try {
            $newMappingVersion = $mappingService->copyMappingVersionToDungeon($sourceMappingVersion, $dungeon);
            $mappingService->copyMappingVersionContentsToDungeon($sourceMappingVersion, $newMappingVersion);
            $enemyForcesCheckpointIdMapping = $mappingService->copyEnemyForcesCheckpointsToMappingVersion(
                $sourceMappingVersion,
                $newMappingVersion,
            );

            // MDT only still reports the surviving enemy - the other one was removed upstream
            $mdtDungeon = $this->createMockPublic(MDTDungeon::class);
            $mdtDungeon->method('getClonesAsEnemies')
                ->willReturn(collect([$this->createMdtCloneOf($survivingEnemy)]));

            // Act
            $importEnemies = new ReflectionMethod(
                $this->app->make(MDTMappingImportServiceInterface::class),
                'importEnemies',
            );
            $importEnemies->invoke(
                $this->app->make(MDTMappingImportServiceInterface::class),
                $sourceMappingVersion,
                $newMappingVersion,
                $mdtDungeon,
                $dungeon,
                $enemyForcesCheckpointIdMapping,
                true,
            );

            // Assert
            /** @var Collection<int, EnemyForcesCheckpoint> $newEnemyForcesCheckpoints */
            $newEnemyForcesCheckpoints = EnemyForcesCheckpoint::query()
                ->where('mapping_version_id', $newMappingVersion->id)
                ->get();

            $this->assertSame(
                ['Kept corridor'],
                $newEnemyForcesCheckpoints->pluck('name')->all(),
                'The checkpoint whose members MDT no longer reports must be dropped, not carried over empty.',
            );

            /** @var EnemyForcesCheckpoint $clonedEnemyForcesCheckpoint */
            $clonedEnemyForcesCheckpoint = $newEnemyForcesCheckpoints->first();

            $this->assertSame(
                $enemyForcesCheckpointIdMapping[$survivingEnemyForcesCheckpoint->id],
                $clonedEnemyForcesCheckpoint->id,
            );

            /** @var Enemy|null $importedEnemy */
            $importedEnemy = Enemy::query()->where('mapping_version_id', $newMappingVersion->id)->first();

            $this->assertNotNull($importedEnemy, 'The MDT enemy should have been imported into the new mapping version.');
            $this->assertSame($survivingEnemy->getUniqueKey(), $importedEnemy->getUniqueKey());
            $this->assertSame(
                $clonedEnemyForcesCheckpoint->id,
                $importedEnemy->enemy_forces_checkpoint_id,
                'The imported enemy must point at the CLONED checkpoint, not at the source mapping version\'s one.',
            );
        } finally {
            if ($newMappingVersion !== null) {
                $newMappingVersion->delete();
            }

            Enemy::query()
                ->whereIn('enemy_forces_checkpoint_id', [$survivingEnemyForcesCheckpoint->id, $emptiedEnemyForcesCheckpoint->id])
                ->update(['enemy_forces_checkpoint_id' => null]);
            EnemyForcesCheckpoint::query()
                ->whereIn('id', [$survivingEnemyForcesCheckpoint->id, $emptiedEnemyForcesCheckpoint->id])
                ->delete();
        }
    }

    /**
     * A dungeon whose current mapping version holds no checkpoints of its own - the assertions below count
     * the checkpoints in the new mapping version, so any hand-made ones in the seeded database would be
     * cloned along and throw the count off.
     */
    private function getDungeonWithoutEnemyForcesCheckpoints(): Dungeon
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()
            ->whereNotNull('challenge_mode_id')
            ->get()
            ->first(static function (Dungeon $dungeon): bool {
                $mappingVersion = $dungeon->getCurrentMappingVersion();

                return $mappingVersion !== null
                    && !$mappingVersion->enemyForcesCheckpoints()->exists()
                    && $mappingVersion->enemies()->whereNull('enemy_forces_checkpoint_id')->count() > 1;
            });

        if ($dungeon === null) {
            $this->fail('No dungeon without enemy forces checkpoints found for testing enemy forces checkpoints.');
        }

        return $dungeon;
    }

    /**
     * Enemies that are not already a member of some checkpoint, so the test can restore them to null.
     *
     * @return array{0: Enemy, 1: Enemy}
     */
    private function getTwoEnemiesWithDistinctUniqueKeys(MappingVersion $mappingVersion): array
    {
        /** @var Collection<string, Enemy> $enemies */
        $enemies = $mappingVersion->enemies()
            ->whereNull('enemy_forces_checkpoint_id')
            ->get()
            ->keyBy(static fn(Enemy $enemy) => $enemy->getUniqueKey())
            ->values();

        if ($enemies->count() < 2) {
            $this->fail('Need at least two enemies with distinct unique keys.');
        }

        return [$enemies[0], $enemies[1]];
    }

    /**
     * Mimics what MDTDungeon::getClonesAsEnemies() hands importEnemies(): an unsaved Enemy carrying the MDT
     * identity (npc + clone index) that Enemy::getUniqueKey() matches predecessors on.
     */
    private function createMdtCloneOf(Enemy $enemy): Enemy
    {
        return new Enemy([
            'npc_id'     => $enemy->npc_id,
            'mdt_npc_id' => $enemy->mdt_npc_id,
            'mdt_id'     => $enemy->mdt_id,
            'floor_id'   => $enemy->floor_id,
            'lat'        => $enemy->lat,
            'lng'        => $enemy->lng,
            'faction'    => $enemy->faction,
        ]);
    }

    private function createEnemyForcesCheckpoint(MappingVersion $mappingVersion, Enemy $enemy, string $name): EnemyForcesCheckpoint
    {
        return EnemyForcesCheckpoint::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $enemy->floor_id,
            'name'               => $name,
            'lat'                => $enemy->lat,
            'lng'                => $enemy->lng,
        ]);
    }
}
