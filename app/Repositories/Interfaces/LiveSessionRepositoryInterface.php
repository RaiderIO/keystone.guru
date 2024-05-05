<?php

namespace App\Repositories\Interfaces;

use App\Models\LiveSession;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method LiveSession create(array $attributes)
 * @method LiveSession find(int $id, array $columns = [])
 * @method LiveSession findOrFail(int $id, array $columns = [])
 * @method LiveSession findOrNew(int $id, array $columns = [])
 * @method bool save(LiveSession $model)
 * @method bool update(LiveSession $model, array $attributes = [], array $options = [])
 * @method bool delete(LiveSession $model)
 * @method Collection<LiveSession> all()
 */
interface LiveSessionRepositoryInterface extends BaseRepositoryInterface
{

}
