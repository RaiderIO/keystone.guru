<?php

namespace App\Service\SimulationCraft;

use App\Logic\SimulationCraft\RaidEventsCollection;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;

interface RaidEventsServiceInterface
{
    /**
     * @return RaidEventsCollection
     */
    public function getRaidEvents(SimulationCraftRaidEventsOptions $options): RaidEventsCollection;
}
