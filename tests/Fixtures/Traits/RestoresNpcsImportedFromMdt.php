<?php

namespace Tests\Fixtures\Traits;

use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Entity\MDTNpc;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Npc\NpcHealth;
use Tests\TestCase;

/**
 * An MDT NPC import overwrites the seeded `npcs` rows of every NPC MDT lists (the seeded name is a
 * translation key, MDT's is English) and inserts health, dungeon and enemy forces rows; none of that is
 * scoped to a mapping version, so nothing cascades it away again.
 *
 * @mixin TestCase
 */
trait RestoresNpcsImportedFromMdt
{
    /**
     * Records the `npcs` rows of every NPC $mdtDungeon lists, and the health and dungeon rows they have, and puts
     * them back when the test's application is torn down - after the test's own `finally`, whatever it throws.
     */
    protected function restoreNpcsImportedFromMdtAfterTheTest(MDTDungeon $mdtDungeon): void
    {
        $mdtNpcIds = $mdtDungeon->getMDTNPCs()->map(static fn(MDTNpc $mdtNpc): int => $mdtNpc->getId())->values()->all();

        /** @var array<int, array<string, mixed>> $npcRows */
        $npcRows = Npc::query()
            ->whereIn('id', $mdtNpcIds)
            ->toBase()
            ->get()
            ->mapWithKeys(static fn(object $npcRow): array => [$npcRow->id => (array)$npcRow])
            ->all();
        $npcHealthIds  = NpcHealth::query()->whereIn('npc_id', $mdtNpcIds)->pluck('id')->all();
        $npcDungeonIds = NpcDungeon::query()->whereIn('npc_id', $mdtNpcIds)->pluck('id')->all();

        $this->beforeApplicationDestroyed(static function () use ($mdtNpcIds, $npcRows, $npcHealthIds, $npcDungeonIds): void {
            // Query-builder writes throughout: these are SeederModels, whose ->delete() is refused on the model instance
            NpcHealth::query()->whereIn('npc_id', $mdtNpcIds)->whereNotIn('id', $npcHealthIds)->delete();
            NpcDungeon::query()->whereIn('npc_id', $mdtNpcIds)->whereNotIn('id', $npcDungeonIds)->delete();

            $insertedNpcIds = array_values(array_diff($mdtNpcIds, array_keys($npcRows)));
            if ($insertedNpcIds !== []) {
                NpcEnemyForces::query()->whereIn('npc_id', $insertedNpcIds)->delete();
                Npc::query()->whereIn('id', $insertedNpcIds)->delete();
            }

            foreach ($npcRows as $npcId => $npcRow) {
                Npc::query()->whereKey($npcId)->update($npcRow);
            }
        });
    }
}
