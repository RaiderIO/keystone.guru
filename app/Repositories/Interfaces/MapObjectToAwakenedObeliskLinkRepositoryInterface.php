<?php

namespace App\Repositories\Interfaces;

use App\Models\MapObjectToAwakenedObeliskLink;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MapObjectToAwakenedObeliskLink create(array $attributes)
 * @method MapObjectToAwakenedObeliskLink find(int $id, array $columns = [])
 * @method MapObjectToAwakenedObeliskLink findOrFail(int $id, array $columns = [])
 * @method MapObjectToAwakenedObeliskLink findOrNew(int $id, array $columns = [])
 * @method bool save(MapObjectToAwakenedObeliskLink $model)
 * @method bool update(MapObjectToAwakenedObeliskLink $model, array $attributes = [], array $options = [])
 * @method bool delete(MapObjectToAwakenedObeliskLink $model)
 * @method Collection<MapObjectToAwakenedObeliskLink> all()
 */
interface MapObjectToAwakenedObeliskLinkRepositoryInterface extends BaseRepositoryInterface
{

}
