<?php

namespace App\Repositories\Interfaces\Timewalking;

use App\Models\Timewalking\TimewalkingEvent;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method TimewalkingEvent                  create(array<string, mixed> $attributes)
 * @method TimewalkingEvent|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method TimewalkingEvent                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method TimewalkingEvent                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                              save(TimewalkingEvent $model)
 * @method bool                              update(TimewalkingEvent $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                              delete(TimewalkingEvent $model)
 * @method Collection<int, TimewalkingEvent> all()
 * @method bool                              exists(array<int, string> $columns)
 */
interface TimewalkingEventRepositoryInterface extends BaseRepositoryInterface
{
}
