<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCases\PublicTestCase;

/**
 * #4426: enemy_forces_override is only ever written by hand in the mapping editor, and importEnemies() rebuilds
 * every enemy from MDT with it nulled. It was absent from the properties recovered from the predecessor, so an
 * MDT mapping bump silently reset every hand-made enemy forces correction in every dungeon.
 *
 * MDT's own per-clone count (6.2.10+) is authoritative where it has one, so it must win over the preserved
 * value rather than be overwritten by it.
 *
 * importEnemies() is exercised directly with a stubbed MDTDungeon, matching
 * MDTMappingImportEnemyForcesCheckpointTest - a real import parses MDT's Lua and rewrites shared NPC/dungeon
 * rows of the seeded test database, none of which this behaviour depends on.
 */
#[Group('MDT')]
#[Group('MappingVersion')]
final class MDTMappingImportEnemyForcesOverrideTest extends PublicTestCase
{
    private const PRESERVED_OVERRIDE = 42;

    private const PRESERVED_OVERRIDE_TEEMING = 43;

    private const MDT_CLONE_OVERRIDE = 12;

    #[Test]
    public function importEnemies_givenPredecessorOverrideAndMdtHasNone_preservesTheOverride(): void
    {
        // Arrange
        [$dungeon, $sourceMappingVersion, $enemy] = $this->getDungeonWithAnEnemy();

        // Act - MDT reports the same enemy, carrying no per-clone count of its own
        $importedEnemy = $this->runImportEnemies($dungeon, $sourceMappingVersion, $enemy, null);

        // Assert
        $this->assertSame(
            self::PRESERVED_OVERRIDE,
            $importedEnemy->enemy_forces_override,
            'A hand-set enemy forces override must survive an MDT mapping bump.',
        );
        $this->assertSame(
            self::PRESERVED_OVERRIDE_TEEMING,
            $importedEnemy->enemy_forces_override_teeming,
            'The teeming counterpart must survive too.',
        );
    }

    #[Test]
    public function importEnemies_givenPredecessorOverrideAndMdtSuppliesItsOwnCloneCount_letsMdtWin(): void
    {
        // Arrange
        [$dungeon, $sourceMappingVersion, $enemy] = $this->getDungeonWithAnEnemy();

        // Act - MDT now states a per-clone count for this enemy, which supersedes the hand-made correction
        $importedEnemy = $this->runImportEnemies($dungeon, $sourceMappingVersion, $enemy, self::MDT_CLONE_OVERRIDE);

        // Assert
        $this->assertSame(
            self::MDT_CLONE_OVERRIDE,
            $importedEnemy->enemy_forces_override,
            'MDT\'s per-clone count is authoritative and must beat the stale hand-set override.',
        );
    }

    /**
     * Gives the predecessor enemy a hand-set override, runs importEnemies() against a stubbed MDT that reports
     * that one enemy, and returns the enemy as it landed in the new mapping version.
     */
    private function runImportEnemies(
        Dungeon        $dungeon,
        MappingVersion $sourceMappingVersion,
        Enemy          $sourceEnemy,
        ?int           $mdtCloneOverride,
    ): Enemy {
        $mappingService    = $this->app->make(MappingServiceInterface::class);
        $newMappingVersion = null;

        $sourceEnemy->update([
            'enemy_forces_override'         => self::PRESERVED_OVERRIDE,
            'enemy_forces_override_teeming' => self::PRESERVED_OVERRIDE_TEEMING,
        ]);

        try {
            $newMappingVersion = $mappingService->copyMappingVersionToDungeon($sourceMappingVersion, $dungeon);
            $mappingService->copyMappingVersionContentsToDungeon($sourceMappingVersion, $newMappingVersion);

            $mdtDungeon = $this->createMockPublic(MDTDungeon::class);
            $mdtDungeon->method('getClonesAsEnemies')
                ->willReturn(collect([$this->createMdtCloneOf($sourceEnemy, $mdtCloneOverride)]));

            $importEnemies = new ReflectionMethod(
                $this->app->make(MDTMappingImportServiceInterface::class),
                'importEnemies',
            );
            $failures = [];
            $importEnemies->invokeArgs(
                $this->app->make(MDTMappingImportServiceInterface::class),
                [
                    $sourceMappingVersion,
                    $newMappingVersion,
                    $mdtDungeon,
                    $dungeon,
                    [],
                    false,
                    &$failures,
                ],
            );

            $this->assertSame([], $failures, 'The import itself must not have failed.');

            /** @var Enemy|null $importedEnemy */
            $importedEnemy = Enemy::query()->where('mapping_version_id', $newMappingVersion->id)->first();
            $this->assertNotNull($importedEnemy, 'The MDT enemy should have been imported into the new mapping version.');

            return $importedEnemy;
        } finally {
            $newMappingVersion?->delete();

            Enemy::query()
                ->whereKey($sourceEnemy->id)
                ->update(['enemy_forces_override' => null, 'enemy_forces_override_teeming' => null]);
        }
    }

    /**
     * @return array{0: Dungeon, 1: MappingVersion, 2: Enemy}
     */
    private function getDungeonWithAnEnemy(): array
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()
            ->whereNotNull('challenge_mode_id')
            ->get()
            ->first(static function (Dungeon $dungeon): bool {
                $mappingVersion = $dungeon->getCurrentMappingVersion();

                return $mappingVersion !== null && $mappingVersion->enemies()->exists();
            });

        if ($dungeon === null) {
            $this->fail('No dungeon with a current mapping version holding enemies found.');
        }

        $mappingVersion = $dungeon->getCurrentMappingVersion();

        /** @var Collection<int, Enemy> $enemies */
        $enemies = $mappingVersion->enemies()->get();

        return [$dungeon, $mappingVersion, $enemies->first()];
    }

    /**
     * Mimics what MDTDungeon::getClonesAsEnemies() hands importEnemies(): an unsaved Enemy carrying the MDT
     * identity (npc + clone index) that Enemy::getUniqueKey() matches predecessors on.
     */
    private function createMdtCloneOf(Enemy $enemy, ?int $enemyForcesOverride): Enemy
    {
        return new Enemy([
            'npc_id'                => $enemy->npc_id,
            'mdt_npc_id'            => $enemy->mdt_npc_id,
            'mdt_id'                => $enemy->mdt_id,
            'floor_id'              => $enemy->floor_id,
            'lat'                   => $enemy->lat,
            'lng'                   => $enemy->lng,
            'faction'               => $enemy->faction,
            'enemy_forces_override' => $enemyForcesOverride,
        ]);
    }
}
