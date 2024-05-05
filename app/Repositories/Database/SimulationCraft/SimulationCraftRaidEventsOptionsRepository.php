<?php

namespace App\Repositories\Database\SimulationCraft;

use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\SimulationCraft\SimulationCraftRaidEventsOptionsRepositoryInterface;

class SimulationCraftRaidEventsOptionsRepository extends DatabaseRepository implements SimulationCraftRaidEventsOptionsRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(SimulationCraftRaidEventsOptions::class);
    }
}
