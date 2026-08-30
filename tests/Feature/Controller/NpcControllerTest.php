<?php

namespace Tests\Feature\Controller;

use App\Events\Models\Npc\NpcChangedEvent;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcEnemyForces;
use App\Models\User;
use Exception;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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

    #[Test]
    public function store_givenNpcMovedToDifferentDungeon_broadcastsExactlyOneChangedEventPerCurrentDungeonAndOneRemovalEventPerFormerDungeon(): void
    {
        // Arrange - an npc assigned only to $dungeonA, then edited to move it to $dungeonB.
        // Regression coverage for #4376: the live-broadcast path used to fire NpcChangedEvent
        // twice for $dungeonA (once for the fresh npc, once for a stale $npcBefore whose
        // ->dungeons relation had already been reloaded post-commit to the *new* set), and never
        // told $dungeonA's editors the npc was actually removed.
        $dungeonA = $this->getDungeonWithCurrentMappingVersionWithEnemies();
        /** @var Dungeon $dungeonB */
        $dungeonB = Dungeon::query()->where('id', '!=', $dungeonA->id)->firstOrFail();

        $templateNpc = Npc::query()->firstOrFail();
        $npcId       = 999999201;

        $npc = Npc::query()->create([
            'id'                => $npcId,
            'classification_id' => $templateNpc->classification_id,
            'npc_type_id'       => $templateNpc->npc_type_id,
            'npc_class_id'      => $templateNpc->npc_class_id,
            'name'              => 'Test Npc - dungeon move broadcast',
            'aggressiveness'    => $templateNpc->aggressiveness,
            'dangerous'         => false,
            'truesight'         => false,
            'runs_away_in_fear' => false,
        ]);

        NpcDungeon::insert([
            'npc_id'     => $npcId,
            'dungeon_id' => $dungeonA->id,
        ]);

        Event::fake([NpcChangedEvent::class]);

        try {
            $this->be(User::findOrFail(self::ADMIN_USER_ID));

            // Act - move the npc from $dungeonA to $dungeonB
            $this->patch(route('admin.npc.update', ['npc' => $npcId]), [
                'id'                        => $npcId,
                'name'                      => $npc->name,
                'classification_id'         => $npc->classification_id,
                'npc_type_id'               => $npc->npc_type_id,
                'npc_class_id'              => $npc->npc_class_id,
                'aggressiveness'            => $npc->aggressiveness,
                'level'                     => $npc->level,
                'dungeon_ids'               => [$dungeonB->id],
                'bolstering_whitelist_npcs' => [],
                'spells'                    => [],
                'submit'                    => 'Submit',
            ])->assertOk();

            // Assert - exactly one event for the dungeon the npc now belongs to (not removed),
            // and exactly one for the dungeon it was removed from (removed), no duplicates.
            Event::assertDispatchedTimes(NpcChangedEvent::class, 2);

            Event::assertDispatched(NpcChangedEvent::class, function (NpcChangedEvent $event) use ($dungeonB) {
                $payload = $event->broadcastWith();

                return $payload['context_route_key'] === $dungeonB->getRouteKey()
                    && $payload['npc_removed_from_dungeon'] === false;
            });

            Event::assertDispatched(NpcChangedEvent::class, function (NpcChangedEvent $event) use ($dungeonA) {
                $payload = $event->broadcastWith();

                return $payload['context_route_key'] === $dungeonA->getRouteKey()
                    && $payload['npc_removed_from_dungeon'] === true;
            });
        } finally {
            NpcEnemyForces::query()->where('npc_id', $npcId)->delete();
            NpcDungeon::query()->where('npc_id', $npcId)->delete();
            Npc::query()->whereKey($npcId)->delete();
        }
    }

    #[Test]
    public function store_givenNpcRenamedToNewId_broadcastsTheOldIdSoConnectedClientsCanReconcile(): void
    {
        // Arrange - regression coverage for #4376 (cold review follow-up): renaming an npc's id
        // remaps `enemies.npc_id`/`npc_enemy_forces.npc_id` in the DB, but a connected client's
        // in-memory enemies/npc list still hold the old id until told otherwise.
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

        $templateNpc = Npc::query()->firstOrFail();
        $oldNpcId    = 999999301;
        $newNpcId    = 999999302;

        $npc = Npc::query()->create([
            'id'                => $oldNpcId,
            'classification_id' => $templateNpc->classification_id,
            'npc_type_id'       => $templateNpc->npc_type_id,
            'npc_class_id'      => $templateNpc->npc_class_id,
            'name'              => 'Test Npc - id rename broadcast',
            'aggressiveness'    => $templateNpc->aggressiveness,
            'dangerous'         => false,
            'truesight'         => false,
            'runs_away_in_fear' => false,
        ]);

        NpcDungeon::insert([
            'npc_id'     => $oldNpcId,
            'dungeon_id' => $dungeon->id,
        ]);

        Event::fake([NpcChangedEvent::class]);

        try {
            $this->be(User::findOrFail(self::ADMIN_USER_ID));

            // Act - rename the npc to $newNpcId, keeping the same dungeon assignment
            $this->patch(route('admin.npc.update', ['npc' => $oldNpcId]), [
                'id'                        => $newNpcId,
                'name'                      => $npc->name,
                'classification_id'         => $npc->classification_id,
                'npc_type_id'               => $npc->npc_type_id,
                'npc_class_id'              => $npc->npc_class_id,
                'aggressiveness'            => $npc->aggressiveness,
                'level'                     => $npc->level,
                'dungeon_ids'               => [$dungeon->id],
                'bolstering_whitelist_npcs' => [],
                'spells'                    => [],
                'submit'                    => 'Submit',
            ])->assertOk();

            // Assert
            Event::assertDispatched(NpcChangedEvent::class, function (NpcChangedEvent $event) use ($dungeon, $newNpcId, $oldNpcId) {
                $payload = $event->broadcastWith();

                return $payload['context_route_key'] === $dungeon->getRouteKey()
                    && $payload['model']['id'] === $newNpcId
                    && $payload['old_npc_id'] === $oldNpcId;
            });
        } finally {
            NpcEnemyForces::query()->where('npc_id', $oldNpcId)->orWhere('npc_id', $newNpcId)->delete();
            NpcDungeon::query()->where('npc_id', $oldNpcId)->orWhere('npc_id', $newNpcId)->delete();
            Npc::query()->where('id', $oldNpcId)->orWhere('id', $newNpcId)->delete();
        }
    }
}
