<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogWalkGraphContribution;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogWalkGraphContribution                  create(array<string, mixed> $attributes)
 * @method CombatLogWalkGraphContribution|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogWalkGraphContribution                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogWalkGraphContribution                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                            save(CombatLogWalkGraphContribution $model)
 * @method bool                                            update(CombatLogWalkGraphContribution $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                            delete(CombatLogWalkGraphContribution $model)
 * @method Collection<int, CombatLogWalkGraphContribution> all()
 * @method bool                                            exists(array<string, mixed> $columns)
 */
interface CombatLogWalkGraphContributionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, int>
     */
    public function getUiMapIds(): Collection;

    public function getLatestId(int $uiMapId): ?int;

    /**
     * The run keys of the most recent runs that walked the ui map, newest first.
     *
     * @return Collection<int, string>
     */
    public function getRecentRunKeys(int $uiMapId, int $limit): Collection;

    /**
     * @param  Collection<int, string>                         $runKeys
     * @return Collection<int, CombatLogWalkGraphContribution> only run_key and edges are loaded
     */
    public function getEdgesForRunKeys(int $uiMapId, Collection $runKeys): Collection;

    /**
     * Deletes the ui map's contributions up to and including an id that are not of one of the kept runs.
     *
     * @param Collection<int, string> $keptRunKeys
     */
    public function pruneOutsideRunKeys(int $uiMapId, Collection $keptRunKeys, int $upToId): int;
}
