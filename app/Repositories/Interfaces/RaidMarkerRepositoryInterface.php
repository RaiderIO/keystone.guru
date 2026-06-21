<?php

namespace App\Repositories\Interfaces;

use App\Models\RaidMarker;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method RaidMarker                  create(array<string, mixed> $attributes)
 * @method RaidMarker|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method RaidMarker                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method RaidMarker                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                        save(RaidMarker $model)
 * @method bool                        update(RaidMarker $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                        delete(RaidMarker $model)
 * @method Collection<int, RaidMarker> all()
 * @method bool                        exists(array<int, string> $columns)
 */
interface RaidMarkerRepositoryInterface extends BaseRepositoryInterface
{
}
