<?php

namespace App\Repositories\Interfaces;

use App\Models\LiveSession\LiveSession;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method LiveSession                  create(array<string, mixed> $attributes)
 * @method LiveSession|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSession                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSession                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                         save(LiveSession $model)
 * @method bool                         update(LiveSession $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                         delete(LiveSession $model)
 * @method Collection<int, LiveSession> all()
 * @method bool                         exists(array<int, string> $columns)
 */
interface LiveSessionRepositoryInterface extends BaseRepositoryInterface
{
}
