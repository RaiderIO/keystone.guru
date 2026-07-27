<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\EnemyForcesCheckpoint;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
#[Group('EnemyForcesCheckpoint')]
final class EnemyForcesCheckpointMappingVersionTest extends PublicTestCase
{
    #[Test]
    public function create_givenMappingVersionWithEnemyForcesCheckpoint_clonesCheckpointAndRelinksItsEnemies(): void
    {
        // Arrange
        $dungeon                = $this->getDungeonWithEnemies();
        $existingMappingVersion = $this->getMappingVersionThatWillBeCloned($dungeon);

        /** @var Enemy $enemy */
        $enemy = $existingMappingVersion->enemies()->firstOrFail();

        $enemyForcesCheckpoint = EnemyForcesCheckpoint::create([
            'mapping_version_id' => $existingMappingVersion->id,
            'floor_id'           => $enemy->floor_id,
            'name'               => 'Test corridor',
            'lat'                => $enemy->lat,
            'lng'                => $enemy->lng,
        ]);

        $enemy->update(['enemy_forces_checkpoint_id' => $enemyForcesCheckpoint->id]);

        $newMappingVersion = null;

        try {
            // Act
            $newMappingVersion = $this->createNextMappingVersion($dungeon, $existingMappingVersion);

            // Assert
            /** @var EnemyForcesCheckpoint|null $clonedCheckpoint */
            $clonedCheckpoint = EnemyForcesCheckpoint::where('mapping_version_id', $newMappingVersion->id)->first();

            $this->assertNotNull($clonedCheckpoint, 'The enemy forces checkpoint should have been cloned into the new MappingVersion.');
            $this->assertSame('Test corridor', $clonedCheckpoint->name);
            $this->assertNotSame($enemyForcesCheckpoint->id, $clonedCheckpoint->id, 'The clone must be a new row.');

            // This is the part that silently breaks: without the second-pass FK re-link in
            // MappingVersion::boot() the cloned enemies keep pointing at the OLD checkpoint, so the new
            // mapping version's checkpoint would report zero enemies.
            $clonedEnemyCheckpointIds = Enemy::where('mapping_version_id', $newMappingVersion->id)
                ->whereNotNull('enemy_forces_checkpoint_id')
                ->pluck('enemy_forces_checkpoint_id')
                ->unique()
                ->values()
                ->all();

            $this->assertSame(
                [$clonedCheckpoint->id],
                $clonedEnemyCheckpointIds,
                'Cloned enemies must point at the cloned checkpoint, not at the checkpoint of the previous mapping version.',
            );
        } finally {
            if ($newMappingVersion !== null) {
                // An Eloquent delete, not a query builder one: MappingVersion::boot()'s `deleting` cascade
                // is what removes everything its `created` hook cloned - enemy packs, patrols and their
                // polylines, map icons, mountable areas, floor unions and areas and npc enemy forces, on top
                // of the enemies and checkpoints this test asserts on. A query builder delete fires no model
                // events, so all of that would leak into the shared test database on every run.
                $newMappingVersion->delete();
            }

            Enemy::where('enemy_forces_checkpoint_id', $enemyForcesCheckpoint->id)
                ->update(['enemy_forces_checkpoint_id' => null]);
            EnemyForcesCheckpoint::where('id', $enemyForcesCheckpoint->id)->delete();
        }
    }

    #[Test]
    public function delete_givenEnemyForcesCheckpointWithEnemies_releasesItsEnemies(): void
    {
        // Arrange
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
        $this->actingAs($admin);

        $dungeon        = $this->getDungeonWithEnemies();
        $mappingVersion = $this->getMappingVersionThatWillBeCloned($dungeon);

        /** @var Enemy $enemy */
        $enemy = $mappingVersion->enemies()->firstOrFail();

        $enemyForcesCheckpoint = EnemyForcesCheckpoint::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $enemy->floor_id,
            'name'               => 'Test corridor',
            'lat'                => $enemy->lat,
            'lng'                => $enemy->lng,
        ]);

        $enemy->update(['enemy_forces_checkpoint_id' => $enemyForcesCheckpoint->id]);

        try {
            // Act
            $enemyForcesCheckpoint->delete();

            // Assert
            // There are no foreign key constraints, so without the model's `deleted` hook the enemy
            // would keep pointing at a checkpoint that no longer exists - and the next checkpoint handed this
            // auto-increment id would silently inherit it.
            $this->assertNull(
                $enemy->fresh()->enemy_forces_checkpoint_id,
                'Deleting a checkpoint must release its member enemies.',
            );
        } finally {
            Enemy::where('enemy_forces_checkpoint_id', $enemyForcesCheckpoint->id)
                ->update(['enemy_forces_checkpoint_id' => null]);
            EnemyForcesCheckpoint::where('id', $enemyForcesCheckpoint->id)->delete();
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
            $this->fail('No dungeon with enemies found for testing enemy forces checkpoints.');
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
