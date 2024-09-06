<?php

namespace App\Logic\SimulationCraft;

use App\Models\Npc\Npc;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;

interface RaidEventPullEnemyInterface
{
    public function calculateHealth(SimulationCraftRaidEventsOptions $options, Npc $npc): int;
}
