<?php

namespace App\Repositories\Database\Npc;

use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\NpcEnemyForcesRepositoryInterface;
use Illuminate\Support\Collection;

class NpcEnemyForcesRepository extends DatabaseRepository implements NpcEnemyForcesRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcEnemyForces::class);
    }

    public function updateNpcId(int $oldNpcId, int $newNpcId): int
    {
        return NpcEnemyForces::where('npc_id', $oldNpcId)->update(['npc_id' => $newNpcId]);
    }

    public function findByMappingVersionAndNpcId(MappingVersion $mappingVersion, int $npcId): ?NpcEnemyForces
    {
        return NpcEnemyForces::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->where('npc_id', $npcId)
            ->first();
    }

    /**
     * @param  Collection<int, int>|null       $npcIds
     * @return Collection<int, NpcEnemyForces>
     */
    public function getByMappingVersionKeyedByNpcId(MappingVersion $mappingVersion, ?Collection $npcIds = null): Collection
    {
        return NpcEnemyForces::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->when($npcIds !== null, static fn($query) => $query->whereIn('npc_id', $npcIds))
            ->get()
            ->keyBy('npc_id');
    }
}
