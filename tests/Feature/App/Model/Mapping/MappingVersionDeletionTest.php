<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Dungeon;
use App\Models\EnemyPack;
use App\Models\Mapping\MappingVersion;
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

        $clonedPackCount = EnemyPack::where('mapping_version_id', $newMappingVersion->id)->count();
        $this->assertGreaterThan(0, $clonedPackCount, 'Enemy packs should have been cloned into the new MappingVersion.');

        try {
            // Act
            $newMappingVersion->delete();

            // Assert
            $this->assertEquals(
                0,
                EnemyPack::where('mapping_version_id', $newMappingVersion->id)->count(),
                'All EnemyPacks for the deleted MappingVersion should have been removed.',
            );
        } finally {
            // Guard: force-clean via query builder in case the test assertion failed before delete
            EnemyPack::where('mapping_version_id', $newMappingVersion->id)->delete();
            MappingVersion::where('id', $newMappingVersion->id)->delete();
        }
    }
}
