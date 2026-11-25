<?php

namespace App\Service\SimulationCraft;

use App\Features\SimulationCraftBossMechanics;
use App\Logic\SimulationCraft\RaidEventsCollection;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Repositories\Interfaces\Npc\NpcEncounterMechanicRepositoryInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use Laravel\Pennant\Feature as Pennant;

readonly class RaidEventsService implements RaidEventsServiceInterface
{
    public function __construct(
        private CoordinatesServiceInterface             $coordinatesService,
        private KillZonePathServiceInterface            $killZonePathService,
        private NpcEncounterMechanicRepositoryInterface $npcEncounterMechanicRepository,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getRaidEvents(SimulationCraftRaidEventsOptions $options): RaidEventsCollection
    {
        $dungeonEncounterMechanics = Pennant::active(SimulationCraftBossMechanics::class) ?
            $this->npcEncounterMechanicRepository->getForDungeon($options->dungeonRoute->dungeon_id) : null;

        return new RaidEventsCollection(
            $this->coordinatesService,
            $this->killZonePathService,
            $options,
            $dungeonEncounterMechanics,
        )->calculateRaidEvents();
    }
}
