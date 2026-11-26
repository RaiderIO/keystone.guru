<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcEncounterMechanic;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcEncounterMechanic                  create(array<string, mixed> $attributes)
 * @method NpcEncounterMechanic|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcEncounterMechanic                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcEncounterMechanic                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                  save(NpcEncounterMechanic $model)
 * @method bool                                  update(NpcEncounterMechanic $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                  delete(NpcEncounterMechanic $model)
 * @method Collection<int, NpcEncounterMechanic> all()
 * @method bool                                  exists(array<string, mixed> $columns)
 */
interface NpcEncounterMechanicRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Replaces every row of the encounter with the given rows, in one transaction.
     *
     * @param list<array{
     *     type: string,
     *     npc_id: int|null,
     *     spell_id: int|null,
     *     first_ms: int|null,
     *     first_health_pct: float|null,
     *     cooldown_ms: int|null,
     *     duration_ms: int|null,
     *     count: int|null,
     *     run_count: int,
     *     total_run_count: int,
     *     data: array<string, mixed>|null
     * }> $rows
     */
    public function replaceForEncounter(int $dungeonId, int $encounterId, array $rows): void;

    /**
     * Every row of every encounter the NPC has a row in, with their npc and spell loaded.
     *
     * @return Collection<int, NpcEncounterMechanic>
     */
    public function getForEncountersOfNpc(int $npcId): Collection;
}
