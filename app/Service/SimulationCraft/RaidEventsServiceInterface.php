<?php

namespace App\Service\SimulationCraft;

use App\Logic\SimulationCraft\RaidEventsCollection;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;

interface RaidEventsServiceInterface
{
    /**
     * @param SimulationCraftRaidEventsOptions $options
     * @return RaidEventsCollection
     */
    public function getRaidEvents(SimulationCraftRaidEventsOptions $options): RaidEventsCollection;
}
