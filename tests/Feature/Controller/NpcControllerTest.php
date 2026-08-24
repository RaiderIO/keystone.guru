<?php

namespace Tests\Feature\Controller;

use App\Models\Enemy;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcEnemyForces;
use App\Models\User;
use Exception;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Npc')]
final class NpcControllerTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const int ADMIN_USER_ID = 1;

    #[Test]
    public function store_givenFailureDuringNpcIdRemap_leavesEnemiesAndNpcEnemyForcesConsistent(): void
    {
        // Arrange - a synthetic NPC with an Enemy and NpcEnemyForces row pointing at it, mirroring
        // the state NpcController::store() remaps from $oldId to the NPC's (possibly changed) id.
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

        /** @var Enemy $templateEnemy */
        $templateEnemy  = $dungeon->getCurrentMappingVersion()->enemies()->firstOrFail();
        $templateNpc    = Npc::query()->firstOrFail();
        $mappingVersion = $templateEnemy->mapping_version_id;

        $oldNpcId = 999999101;
        $newNpcId = 999999102;
        $enemyId  = 999999103;

        $npc = Npc::query()->create([
            'id'                => $oldNpcId,
            'classification_id' => $templateNpc->classification_id,
            'npc_type_id'       => $templateNpc->npc_type_id,
            'npc_class_id'      => $templateNpc->npc_class_id,
            'name'              => 'Test Npc - store() atomicity',
            'aggressiveness'    => $templateNpc->aggressiveness,
            'dangerous'         => false,
            'truesight'         => false,
            'runs_away_in_fear' => false,
        ]);

        NpcDungeon::insert([
            'npc_id'     => $oldNpcId,
            'dungeon_id' => $dungeon->id,
        ]);

        Enemy::query()->create([
            'id'                 => $enemyId,
            'mapping_version_id' => $mappingVersion,
            'floor_id'           => $templateEnemy->floor_id,
            'npc_id'             => $oldNpcId,
            'faction'            => $templateEnemy->faction,
            'required'           => false,
            'skippable'          => false,
            'hyper_respawn'      => false,
            'lat'                => $templateEnemy->lat,
            'lng'                => $templateEnemy->lng,
        ]);

        NpcEnemyForces::query()->create([
            'mapping_version_id' => $mappingVersion,
            'npc_id'             => $oldNpcId,
            'enemy_forces'       => 10,
        ]);

        // Simulate a failure between the Enemy remap (:132) and the NpcEnemyForces remap (:133) -
        // exactly the window the issue calls out as the worst case.
        DB::listen(static function (QueryExecuted $query): void {
            if (str_contains($query->sql, 'update `enemies`')) {
                throw new Exception('Simulated failure mid-remap');
            }
        });

        try {
            try {
                $this->be(User::findOrFail(self::ADMIN_USER_ID));
                $this->withoutExceptionHandling();
                $this->expectExceptionMessage('Simulated failure mid-remap');

                // Act
                $this->patch(route('admin.npc.update', ['npc' => $oldNpcId]), [
                    'id'                        => $newNpcId,
                    'name'                      => 'Test Npc - store() atomicity (renamed)',
                    'classification_id'         => $templateNpc->classification_id,
                    'npc_type_id'               => $templateNpc->npc_type_id,
                    'npc_class_id'              => $templateNpc->npc_class_id,
                    'aggressiveness'            => $templateNpc->aggressiveness,
                    'level'                     => $templateNpc->level,
                    'dungeon_ids'               => [$dungeon->id],
                    'bolstering_whitelist_npcs' => [],
                    'spells'                    => [],
                    'submit'                    => 'Submit',
                ]);
            } finally {
                // Assert - the whole remap must have rolled back: enemies and npc_enemy_forces still
                // agree with each other (and with the NPC row), instead of pointing at different ids.
                $this->assertTrue(Npc::query()->whereKey($oldNpcId)->exists(), 'NPC must still exist under its old id after rollback.');
                $this->assertFalse(Npc::query()->whereKey($newNpcId)->exists(), 'NPC must not have been remapped to the new id after rollback.');

                $this->assertSame($oldNpcId, Enemy::query()->whereKey($enemyId)->value('npc_id'), 'Enemy must still point at the old NPC id after rollback.');
                $this->assertSame(
                    $oldNpcId,
                    NpcEnemyForces::query()->where('mapping_version_id', $mappingVersion)->whereIn('npc_id', [$oldNpcId, $newNpcId])->value('npc_id'),
                    'NpcEnemyForces must still point at the old NPC id after rollback, matching the Enemy row.',
                );
            }
        } finally {
            NpcEnemyForces::query()->where('npc_id', $oldNpcId)->orWhere('npc_id', $newNpcId)->delete();
            Enemy::query()->whereKey($enemyId)->delete();
            NpcDungeon::query()->where('npc_id', $oldNpcId)->orWhere('npc_id', $newNpcId)->delete();
            Npc::query()->where('id', $oldNpcId)->orWhere('id', $newNpcId)->delete();
        }
    }
}
