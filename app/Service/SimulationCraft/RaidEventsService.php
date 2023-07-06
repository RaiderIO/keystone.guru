<?php

namespace App\Service\SimulationCraft;

use App\Logic\SimulationCraft\RaidEventsCollection;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;

class RaidEventsService implements RaidEventsServiceInterface
{
    /**
     * @inheritDoc
     */
    public function getRaidEvents(SimulationCraftRaidEventsOptions $options): RaidEventsCollection
    {
        return (new RaidEventsCollection($options))
            ->calculateRaidEvents();
    }
}
