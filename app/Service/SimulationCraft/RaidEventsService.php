<?php

namespace App\Service\SimulationCraft;

use App\Logic\SimulationCraft\RaidEventsCollection;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Service\Coordinates\CoordinatesServiceInterface;

class RaidEventsService implements RaidEventsServiceInterface
{
    public function __construct(private readonly CoordinatesServiceInterface $coordinatesService)
    {
    }

    /**
     * @inheritDoc
     */
    public function getRaidEvents(SimulationCraftRaidEventsOptions $options): RaidEventsCollection
    {
        return (new RaidEventsCollection($this->coordinatesService, $options))
            ->calculateRaidEvents();
    }
}
