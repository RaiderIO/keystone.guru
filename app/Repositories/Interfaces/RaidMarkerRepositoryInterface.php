<?php

namespace App\Repositories\Interfaces;

use App\Models\RaidMarker;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method RaidMarker create(array $attributes)
 * @method RaidMarker find(int $id, array $columns = [])
 * @method RaidMarker findOrFail(int $id, array $columns = [])
 * @method RaidMarker findOrNew(int $id, array $columns = [])
 * @method bool save(RaidMarker $model)
 * @method bool update(RaidMarker $model, array $attributes = [], array $options = [])
 * @method bool delete(RaidMarker $model)
 * @method Collection<RaidMarker> all()
 */
interface RaidMarkerRepositoryInterface extends BaseRepositoryInterface
{

}
