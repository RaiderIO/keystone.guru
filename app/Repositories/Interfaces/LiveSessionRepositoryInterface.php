<?php

namespace App\Repositories\Interfaces;

use App\Models\LiveSession;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method LiveSession create(array $attributes)
 * @method LiveSession|null find(int $id, array|string $columns = ['*'])
 * @method LiveSession findOrFail(int $id, array|string $columns = ['*'])
 * @method LiveSession findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(LiveSession $model)
 * @method bool update(LiveSession $model, array $attributes = [], array $options = [])
 * @method bool delete(LiveSession $model)
 * @method Collection<LiveSession> all()
 */
interface LiveSessionRepositoryInterface extends BaseRepositoryInterface
{

}
