<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Npc             create(array $attributes)
 * @method Npc|null        find(int $id, array|string $columns = ['*'])
 * @method Npc             findOrFail(int $id, array|string $columns = ['*'])
 * @method Npc             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool            save(Npc $model)
 * @method bool            update(Npc $model, array $attributes = [], array $options = [])
 * @method bool            delete(Npc $model)
 * @method Collection<Npc> all()
 */
interface NpcRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<Npc>
     */
    public function getInUseNpcs(MappingVersion $mappingVersion): Collection;

    /**
     * @return Collection<int>
     */
    public function getInUseNpcIds(MappingVersion $mappingVersion, ?Collection $inUseNpcs = null): Collection;
}
