<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Dungeon;
use App\Models\EnemyPack;
use App\Models\Mapping\MappingVersion;
use App\Models\Polyline;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
final class MappingVersionDeletionTest extends PublicTestCase
{
    #[Test]
    public function delete_givenMappingVersionWithClonedEnemyPacks_deletesEnemyPacksToo(): void
    {
        // Arrange
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')
            ->get()
            ->first(static function (Dungeon $dungeon): bool {
                $mappingVersion = $dungeon->getCurrentMappingVersion();

                return $mappingVersion !== null && $mappingVersion->enemyPacks()->exists();
            });

        if ($dungeon === null) {
            $this->fail('No dungeon with enemy packs found for testing MappingVersion deletion.');
        }

        $existingMappingVersion = $dungeon->getCurrentMappingVersion();

        $newMappingVersion = MappingVersion::create([
            'game_version_id'                 => $existingMappingVersion->game_version_id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $existingMappingVersion->version + 1000,
            'enemy_forces_required'           => $existingMappingVersion->enemy_forces_required,
            'enemy_forces_required_teeming'   => $existingMappingVersion->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $existingMappingVersion->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $existingMappingVersion->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $existingMappingVersion->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);

        $clonedPackIds = EnemyPack::where('mapping_version_id', $newMappingVersion->id)->pluck('id');
        $this->assertGreaterThan(0, $clonedPackIds->count(), 'Enemy packs should have been cloned into the new MappingVersion.');

        try {
            // Act
            $newMappingVersion->delete();

            // Assert
            $this->assertEquals(
                0,
                EnemyPack::where('mapping_version_id', $newMappingVersion->id)->count(),
                'All EnemyPacks for the deleted MappingVersion should have been removed.',
            );
            $this->assertEquals(
                0,
                Polyline::where('model_class', EnemyPack::class)->whereIn('model_id', $clonedPackIds)->count(),
                'The polylines of the deleted EnemyPacks should have been removed with them.',
            );
        } finally {
            // Guard: force-clean via query builder in case the test assertion failed before delete
            Polyline::where('model_class', EnemyPack::class)->whereIn('model_id', $clonedPackIds)->delete();
            EnemyPack::where('mapping_version_id', $newMappingVersion->id)->delete();
            MappingVersion::where('id', $newMappingVersion->id)->delete();
        }
    }

    #[Test]
    public function create_givenPreviousMappingVersionWithEnemyPacks_givesEachClonedPackItsOwnCopyOfThePolyline(): void
    {
        // Arrange
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')
            ->get()
            ->first(static function (Dungeon $dungeon): bool {
                $mappingVersion = $dungeon->getCurrentMappingVersion();

                return $mappingVersion !== null && $mappingVersion->enemyPacks()->exists();
            });

        if ($dungeon === null) {
            $this->fail('No dungeon with enemy packs found for testing MappingVersion cloning.');
        }

        $existingMappingVersion = $dungeon->getCurrentMappingVersion();
        $existingPolylines      = $existingMappingVersion->enemyPacks()->get()
            ->map(static fn(EnemyPack $enemyPack) => sprintf('%s %s', $enemyPack->polyline?->color, $enemyPack->polyline?->vertices_json))
            ->sort()
            ->values();

        // Act
        $newMappingVersion = MappingVersion::create([
            'game_version_id'                 => $existingMappingVersion->game_version_id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $existingMappingVersion->version + 1000,
            'enemy_forces_required'           => $existingMappingVersion->enemy_forces_required,
            'enemy_forces_required_teeming'   => $existingMappingVersion->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $existingMappingVersion->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $existingMappingVersion->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $existingMappingVersion->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);

        try {
            // Assert
            $clonedEnemyPacks = $newMappingVersion->enemyPacks()->get();
            $this->assertEquals(
                $existingPolylines,
                $clonedEnemyPacks
                    ->map(static fn(EnemyPack $enemyPack) => sprintf('%s %s', $enemyPack->polyline?->color, $enemyPack->polyline?->vertices_json))
                    ->sort()
                    ->values(),
            );

            foreach ($clonedEnemyPacks as $clonedEnemyPack) {
                $this->assertNotNull($clonedEnemyPack->polyline);
                $this->assertSame($clonedEnemyPack->polyline->id, $clonedEnemyPack->polyline_id);
                $this->assertNull($clonedEnemyPack->getRawOriginal('vertices_json'));
            }
        } finally {
            $newMappingVersion->delete();
        }
    }
}
