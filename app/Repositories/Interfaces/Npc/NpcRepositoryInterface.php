<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Npc                  create(array<string, mixed> $attributes)
 * @method Npc|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Npc                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Npc                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                 save(Npc $model)
 * @method bool                 update(Npc $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                 delete(Npc $model)
 * @method Collection<int, Npc> all()
 * @method bool                 exists(array<string, mixed> $columns)
 */
interface NpcRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, Npc>
     */
    public function getInUseNpcs(MappingVersion $mappingVersion): Collection;

    /**
     * @param  Collection<int, Npc>|null $inUseNpcs
     * @return Collection<int, int>
     */
    public function getInUseNpcIds(MappingVersion $mappingVersion, ?Collection $inUseNpcs = null): Collection;
}
