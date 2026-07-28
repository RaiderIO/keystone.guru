<?php

namespace Tests\Feature\App\Service\Mapping;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\EnemyForcesCheckpoint;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * #3702: an MDT mapping import clones a mapping version through MappingService, deliberately bypassing the
 * MappingVersion::boot() clone hook. That path copies no enemies, so the checkpoints it clones arrive without
 * members - the source id => clone id mapping copyEnemyForcesCheckpointsToMappingVersion() returns is what
 * lets the caller re-link membership itself. A bare mapping version has no enemies at all and deliberately
 * does not call this method, so its checkpoints stay empty until enemies are assigned to them by hand.
 */
#[Group('MappingVersion')]
#[Group('EnemyForcesCheckpoint')]
final class CopyMappingVersionContentsEnemyForcesCheckpointTest extends PublicTestCase
{
    #[Test]
    public function copyEnemyForcesCheckpointsToMappingVersion_givenSourceWithEnemyForcesCheckpoint_clonesItAndReturnsTheIdMapping(): void
    {
        // Arrange
        $mappingService = $this->app->make(MappingServiceInterface::class);

        $dungeon               = $this->getDungeonWithoutEnemyForcesCheckpoints();
        $sourceMappingVersion  = $dungeon->getCurrentMappingVersion();
        $enemyForcesCheckpoint = $this->createEnemyForcesCheckpoint($sourceMappingVersion, 'Test corridor');

        $targetMappingVersion = null;

        try {
            // Act - exactly what MDTMappingImportService does on a mapping re-import
            $targetMappingVersion = $mappingService->copyMappingVersionToDungeon($sourceMappingVersion, $dungeon);
            $mappingService->copyMappingVersionContentsToDungeon($sourceMappingVersion, $targetMappingVersion);
            $enemyForcesCheckpointIdMapping = $mappingService->copyEnemyForcesCheckpointsToMappingVersion(
                $sourceMappingVersion,
                $targetMappingVersion,
            );

            // Assert
            /** @var EnemyForcesCheckpoint|null $clonedEnemyForcesCheckpoint */
            $clonedEnemyForcesCheckpoint = EnemyForcesCheckpoint::query()
                ->where('mapping_version_id', $targetMappingVersion->id)
                ->first();

            $this->assertNotNull($clonedEnemyForcesCheckpoint, 'The checkpoint should have been cloned into the target mapping version.');
            $this->assertSame('Test corridor', $clonedEnemyForcesCheckpoint->name);
            $this->assertNotSame($enemyForcesCheckpoint->id, $clonedEnemyForcesCheckpoint->id, 'The clone must be a new row.');

            $this->assertSame(
                [$enemyForcesCheckpoint->id => $clonedEnemyForcesCheckpoint->id],
                $enemyForcesCheckpointIdMapping,
                'The caller must be handed the source id => clone id mapping so it can re-link membership itself.',
            );

            // This is why the mapping has to be returned at all: neither method copies enemies, so nothing in
            // the target mapping version points at the cloned checkpoint yet.
            $this->assertSame(
                0,
                Enemy::query()->where('mapping_version_id', $targetMappingVersion->id)->count(),
                'copyMappingVersionContentsToDungeon() must not copy enemies.',
            );
        } finally {
            if ($targetMappingVersion !== null) {
                // An Eloquent delete: MappingVersion::boot()'s `deleting` cascade is what removes everything
                // that was cloned into it. A query builder delete fires no model events, so all of it would
                // leak into the shared test database on every run.
                $targetMappingVersion->delete();
            }

            EnemyForcesCheckpoint::query()->where('id', $enemyForcesCheckpoint->id)->delete();
        }
    }

    /**
     * A dungeon whose current mapping version holds no checkpoints of its own - the assertions below expect a
     * single clone, so any hand-made ones in the seeded database would be cloned along and throw that off.
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
                    && $mappingVersion->enemies()->exists();
            });

        if ($dungeon === null) {
            $this->fail('No dungeon without enemy forces checkpoints found for testing enemy forces checkpoints.');
        }

        return $dungeon;
    }

    private function createEnemyForcesCheckpoint(MappingVersion $mappingVersion, string $name): EnemyForcesCheckpoint
    {
        /** @var Enemy $enemy */
        $enemy = $mappingVersion->enemies()->firstOrFail();

        return EnemyForcesCheckpoint::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $enemy->floor_id,
            'name'               => $name,
            'lat'                => $enemy->lat,
            'lng'                => $enemy->lng,
        ]);
    }
}
