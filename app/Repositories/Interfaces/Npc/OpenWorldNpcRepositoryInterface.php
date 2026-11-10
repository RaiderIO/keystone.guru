<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\OpenWorldNpc;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method OpenWorldNpc                  create(array<string, mixed> $attributes)
 * @method OpenWorldNpc|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method OpenWorldNpc                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method OpenWorldNpc                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                          save(OpenWorldNpc $model)
 * @method bool                          update(OpenWorldNpc $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                          delete(OpenWorldNpc $model)
 * @method Collection<int, OpenWorldNpc> all()
 * @method bool                          exists(array<string, mixed> $columns)
 */
interface OpenWorldNpcRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * The open-world NPCs with any of the given ids, keyed by id.
     *
     * @param  Collection<int, int>          $ids
     * @return Collection<int, OpenWorldNpc>
     */
    public function findAllByIdKeyed(Collection $ids): Collection;

    /**
     * The open-world NPCs that have no display id yet, ordered by id.
     *
     * @return Collection<int, OpenWorldNpc>
     */
    public function getWithoutDisplayId(?int $limit = null): Collection;

    /**
     * Every open-world NPC that has no portrait yet, ordered by id.
     *
     * @return Collection<int, OpenWorldNpc>
     */
    public function getWithoutPortrait(): Collection;
}
