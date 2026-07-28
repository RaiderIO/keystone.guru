<?php

namespace App\Repositories\Interfaces;

use App\Models\EnemyForcesCheckpoint;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method EnemyForcesCheckpoint                  create(array<string, mixed> $attributes)
 * @method EnemyForcesCheckpoint|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method EnemyForcesCheckpoint                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method EnemyForcesCheckpoint                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                   save(EnemyForcesCheckpoint $model)
 * @method bool                                   update(EnemyForcesCheckpoint $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                   delete(EnemyForcesCheckpoint $model)
 * @method Collection<int, EnemyForcesCheckpoint> all()
 * @method bool                                   exists(array<int, string> $columns)
 */
interface EnemyForcesCheckpointRepositoryInterface extends BaseRepositoryInterface
{
}
