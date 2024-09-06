<?php

namespace App\Repositories\Interfaces\Timewalking;

use App\Models\Timewalking\TimewalkingEvent;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method TimewalkingEvent create(array $attributes)
 * @method TimewalkingEvent|null find(int $id, array|string $columns = ['*'])
 * @method TimewalkingEvent findOrFail(int $id, array|string $columns = ['*'])
 * @method TimewalkingEvent findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(TimewalkingEvent $model)
 * @method bool update(TimewalkingEvent $model, array $attributes = [], array $options = [])
 * @method bool delete(TimewalkingEvent $model)
 * @method Collection<TimewalkingEvent> all()
 */
interface TimewalkingEventRepositoryInterface extends BaseRepositoryInterface
{

}
