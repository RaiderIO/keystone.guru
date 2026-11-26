<?php

namespace App\Repositories\Interfaces\LiveSession;

use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionEnemyState;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method LiveSessionEnemyState                  create(array<string, mixed> $attributes)
 * @method LiveSessionEnemyState|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionEnemyState                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionEnemyState                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                   save(LiveSessionEnemyState $model)
 * @method bool                                   update(LiveSessionEnemyState $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                   delete(LiveSessionEnemyState $model)
 * @method Collection<int, LiveSessionEnemyState> all()
 * @method bool                                   exists(array<int, string> $columns)
 */
interface LiveSessionEnemyStateRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Record one enemy in the given state. Recording an enemy that is already in that state changes nothing.
     *
     * @return bool True if the enemy was newly recorded, false if it already was in that state.
     */
    public function addEnemy(LiveSession $liveSession, LiveSessionEnemyStateType $state, int $npcId, int $mdtId): bool;

    /**
     * Take one enemy out of the given state. Removing an enemy that is not in that state changes nothing.
     *
     * @return bool True if the enemy was in that state, false if it was not.
     */
    public function removeEnemy(LiveSession $liveSession, LiveSessionEnemyStateType $state, int $npcId, int $mdtId): bool;

    /**
     * Replace every enemy in the given state with exactly these enemies.
     *
     * @param Collection<int, Enemy> $enemies
     */
    public function replaceEnemies(LiveSession $liveSession, LiveSessionEnemyStateType $state, Collection $enemies): void;

    /**
     * The IDs of the enemies in the given state, resolved on the route's mapping version.
     *
     * @return Collection<int, int>
     */
    public function getEnemyIds(LiveSession $liveSession, LiveSessionEnemyStateType $state): Collection;
}
