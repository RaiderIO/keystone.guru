<?php

namespace App\Repositories\Interfaces\SimulationCraft;

use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method SimulationCraftRaidEventsOptions create(array $attributes)
 * @method SimulationCraftRaidEventsOptions find(int $id, array $columns = [])
 * @method SimulationCraftRaidEventsOptions findOrFail(int $id, array $columns = [])
 * @method SimulationCraftRaidEventsOptions findOrNew(int $id, array $columns = [])
 * @method bool save(SimulationCraftRaidEventsOptions $model)
 * @method bool update(SimulationCraftRaidEventsOptions $model, array $attributes = [], array $options = [])
 * @method bool delete(SimulationCraftRaidEventsOptions $model)
 * @method Collection<SimulationCraftRaidEventsOptions> all()
 */
interface SimulationCraftRaidEventsOptionsRepositoryInterface extends BaseRepositoryInterface
{

}
