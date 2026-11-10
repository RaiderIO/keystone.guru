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

    /**
     * The open-world NPCs the in-game portrait pipeline can take next: no portrait yet, a known display id and, unless
     * asked for, not already found unrenderable. Ordered by id.
     *
     * @return Collection<int, OpenWorldNpc>
     */
    public function getPortraitCandidates(int $limit, bool $includeUnrenderable): Collection;

    /**
     * How many open-world NPCs {@see getPortraitCandidates()} would return without a limit.
     */
    public function countPortraitCandidates(bool $includeUnrenderable): int;

    /**
     * The open-world NPCs the in-game portrait pipeline could not render, ordered by id.
     *
     * @return Collection<int, OpenWorldNpc>
     */
    public function getUnrenderablePortraits(): Collection;

    /**
     * Sets the portrait state of the open-world NPCs with the given ids.
     *
     * @param  Collection<int, int> $ids
     * @return int                  The number of rows updated
     */
    public function updatePortraitState(Collection $ids, bool $hasPortrait, bool $portraitUnrenderable): int;
}
