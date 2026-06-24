<?php

namespace App\Repositories\Interfaces;

use App\Models\MapObjectToAwakenedObeliskLink;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MapObjectToAwakenedObeliskLink                  create(array<string, mixed> $attributes)
 * @method MapObjectToAwakenedObeliskLink|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method MapObjectToAwakenedObeliskLink                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method MapObjectToAwakenedObeliskLink                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                            save(MapObjectToAwakenedObeliskLink $model)
 * @method bool                                            update(MapObjectToAwakenedObeliskLink $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                            delete(MapObjectToAwakenedObeliskLink $model)
 * @method Collection<int, MapObjectToAwakenedObeliskLink> all()
 * @method bool                                            exists(array<int, string> $columns)
 */
interface MapObjectToAwakenedObeliskLinkRepositoryInterface extends BaseRepositoryInterface
{
}
