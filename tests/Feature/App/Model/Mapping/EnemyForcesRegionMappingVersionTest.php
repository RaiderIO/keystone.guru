<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\EnemyForcesRegion;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
#[Group('EnemyForcesRegion')]
final class EnemyForcesRegionMappingVersionTest extends PublicTestCase
{
    #[Test]
    public function create_givenMappingVersionWithEnemyForcesRegion_clonesRegionAndRelinksItsEnemies(): void
    {
        // Arrange
        $dungeon                = $this->getDungeonWithEnemies();
        $existingMappingVersion = $this->getMappingVersionThatWillBeCloned($dungeon);

        /** @var Enemy $enemy */
        $enemy = $existingMappingVersion->enemies()->firstOrFail();

        $enemyForcesRegion = EnemyForcesRegion::create([
            'mapping_version_id' => $existingMappingVersion->id,
            'floor_id'           => $enemy->floor_id,
            'name'               => 'Test corridor',
            'lat'                => $enemy->lat,
            'lng'                => $enemy->lng,
        ]);

        $enemy->update(['enemy_forces_region_id' => $enemyForcesRegion->id]);

        $newMappingVersion = null;

        try {
            // Act
            $newMappingVersion = $this->createNextMappingVersion($dungeon, $existingMappingVersion);

            // Assert
            /** @var EnemyForcesRegion|null $clonedRegion */
            $clonedRegion = EnemyForcesRegion::where('mapping_version_id', $newMappingVersion->id)->first();

            $this->assertNotNull($clonedRegion, 'The enemy forces region should have been cloned into the new MappingVersion.');
            $this->assertSame('Test corridor', $clonedRegion->name);
            $this->assertNotSame($enemyForcesRegion->id, $clonedRegion->id, 'The clone must be a new row.');

            // This is the part that silently breaks: without the second-pass FK re-link in
            // MappingVersion::boot() the cloned enemies keep pointing at the OLD region, so the new
            // mapping version's region would report zero enemies.
            $clonedEnemyRegionIds = Enemy::where('mapping_version_id', $newMappingVersion->id)
                ->whereNotNull('enemy_forces_region_id')
                ->pluck('enemy_forces_region_id')
                ->unique()
                ->values()
                ->all();

            $this->assertSame(
                [$clonedRegion->id],
                $clonedEnemyRegionIds,
                'Cloned enemies must point at the cloned region, not at the region of the previous mapping version.',
            );
        } finally {
            if ($newMappingVersion !== null) {
                Enemy::where('mapping_version_id', $newMappingVersion->id)->delete();
                EnemyForcesRegion::where('mapping_version_id', $newMappingVersion->id)->delete();
                MappingVersion::where('id', $newMappingVersion->id)->delete();
            }

            Enemy::where('enemy_forces_region_id', $enemyForcesRegion->id)
                ->update(['enemy_forces_region_id' => null]);
            EnemyForcesRegion::where('id', $enemyForcesRegion->id)->delete();
        }
    }

    #[Test]
    public function delete_givenEnemyForcesRegionWithEnemies_releasesItsEnemies(): void
    {
        // Arrange
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
        $this->actingAs($admin);

        $dungeon        = $this->getDungeonWithEnemies();
        $mappingVersion = $this->getMappingVersionThatWillBeCloned($dungeon);

        /** @var Enemy $enemy */
        $enemy = $mappingVersion->enemies()->firstOrFail();

        $enemyForcesRegion = EnemyForcesRegion::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $enemy->floor_id,
            'name'               => 'Test corridor',
            'lat'                => $enemy->lat,
            'lng'                => $enemy->lng,
        ]);

        $enemy->update(['enemy_forces_region_id' => $enemyForcesRegion->id]);

        try {
            // Act
            $enemyForcesRegion->delete();

            // Assert
            // There are no foreign key constraints, so without the model's `deleted` hook the enemy
            // would keep pointing at a region that no longer exists - and the next region handed this
            // auto-increment id would silently inherit it.
            $this->assertNull(
                $enemy->fresh()->enemy_forces_region_id,
                'Deleting a region must release its member enemies.',
            );
        } finally {
            Enemy::where('enemy_forces_region_id', $enemyForcesRegion->id)
                ->update(['enemy_forces_region_id' => null]);
            EnemyForcesRegion::where('id', $enemyForcesRegion->id)->delete();
        }
    }

    private function getDungeonWithEnemies(): Dungeon
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')
            ->get()
            ->first(static function (Dungeon $dungeon): bool {
                /** @var MappingVersion|null $mappingVersion */
                $mappingVersion = $dungeon->mappingVersions()->first();

                return $mappingVersion !== null && $mappingVersion->enemies()->exists();
            });

        if ($dungeon === null) {
            $this->fail('No dungeon with enemies found for testing enemy forces regions.');
        }

        return $dungeon;
    }

    /**
     * The mapping version MappingVersion::boot() will clone from - the highest `version` of the
     * dungeon across every game version, which is not necessarily what getCurrentMappingVersion()
     * returns (that one is scoped to a single game version).
     */
    private function getMappingVersionThatWillBeCloned(Dungeon $dungeon): MappingVersion
    {
        /** @var MappingVersion|null $mappingVersion */
        $mappingVersion = $dungeon->mappingVersions()->first();

        if ($mappingVersion === null) {
            $this->fail('Dungeon has no mapping versions.');
        }

        return $mappingVersion;
    }

    private function createNextMappingVersion(Dungeon $dungeon, MappingVersion $existingMappingVersion): MappingVersion
    {
        return MappingVersion::create([
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
    }
}
