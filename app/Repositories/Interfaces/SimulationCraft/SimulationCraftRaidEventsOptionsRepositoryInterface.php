<?php

namespace App\Repositories\Interfaces\SimulationCraft;

use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method SimulationCraftRaidEventsOptions                  create(array<string, mixed> $attributes)
 * @method SimulationCraftRaidEventsOptions|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method SimulationCraftRaidEventsOptions                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method SimulationCraftRaidEventsOptions                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                              save(SimulationCraftRaidEventsOptions $model)
 * @method bool                                              update(SimulationCraftRaidEventsOptions $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                              delete(SimulationCraftRaidEventsOptions $model)
 * @method Collection<int, SimulationCraftRaidEventsOptions> all()
 * @method bool                                              exists(array<int, string> $columns)
 */
interface SimulationCraftRaidEventsOptionsRepositoryInterface extends BaseRepositoryInterface
{
}
