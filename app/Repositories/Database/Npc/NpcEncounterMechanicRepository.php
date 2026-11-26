<?php

namespace App\Repositories\Database\Npc;

use App\Models\Npc\NpcEncounterMechanic;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\NpcEncounterMechanicRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NpcEncounterMechanicRepository extends DatabaseRepository implements NpcEncounterMechanicRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcEncounterMechanic::class);
    }

    public function replaceForEncounter(int $dungeonId, int $encounterId, array $rows): void
    {
        $now = Carbon::now();

        NpcEncounterMechanic::query()->getConnection()->transaction(static function () use ($dungeonId, $encounterId, $rows, $now) {
            NpcEncounterMechanic::query()
                ->where('dungeon_id', $dungeonId)
                ->where('encounter_id', $encounterId)
                ->toBase()
                ->delete();

            if ($rows === []) {
                return;
            }

            NpcEncounterMechanic::query()->insert(array_map(static fn(array $row) => array_merge($row, [
                'dungeon_id'   => $dungeonId,
                'encounter_id' => $encounterId,
                'data'         => $row['data'] === null ? null : json_encode($row['data']),
                'created_at'   => $now,
                'updated_at'   => $now,
            ]), $rows));
        }, 3);
    }

    public function getForEncountersOfNpc(int $npcId): Collection
    {
        $encounters = NpcEncounterMechanic::query()
            ->where('npc_id', $npcId)
            ->distinct()
            ->get(['dungeon_id', 'encounter_id']);

        if ($encounters->isEmpty()) {
            return collect();
        }

        return NpcEncounterMechanic::query()
            ->with(['npc', 'spell.descriptionTranslation'])
            ->where(static function (Builder $query) use ($encounters) {
                foreach ($encounters as $encounter) {
                    $query->orWhere(static function (Builder $query) use ($encounter) {
                        $query->where('dungeon_id', $encounter->dungeon_id)
                            ->where('encounter_id', $encounter->encounter_id);
                    });
                }
            })
            ->orderBy('id')
            ->get()
            ->toBase();
    }

    public function getForDungeon(int $dungeonId): Collection
    {
        return NpcEncounterMechanic::query()
            ->with(['npc.npcHealths'])
            ->where('dungeon_id', $dungeonId)
            ->orderBy('id')
            ->get()
            ->toBase();
    }
}
